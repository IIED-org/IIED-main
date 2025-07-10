/*
 * Copyright (c) 2003-2025, CKSource Holding sp. z o.o. All rights reserved.
 * For licensing, see https://ckeditor.com/legal/ckeditor-oss-license
 */

const { View } = window.CKEditor5.ui;
const { Rect, Collection } = window.CKEditor5.utils;
const { Plugin } = window.CKEditor5.core;

const definitions = [
  {
    header: 'Oops...',
    description: 'It seems that the editor encountered an error. Save your content and refresh the page. If the error persists, contact your site administrator.',
    type: 'error',
    reactsTo: { name: 'CKEditorError' }
  },
  {
    header: 'Trial limit exceeded',
    description: 'You have exhausted the trial usage limit. Restart the editor - you can reload the page or save edited content.',
    type: 'error',
    reactsTo: { message: 'trial-license-key-reached-limit' }
  },
  {
    header: 'Trial limit exceeded',
    description: 'You have reached the usage limit of your trial license key. Restart the editor - you can reload the page or save edited content.',
    type: 'error',
    reactsTo: { message: 'license-key-trial-limit' }
  },
  {
    header: 'Usage limit reached',
    description: 'You have reached the usage limit of your license key. Please contact our customer support to extend the limit at https://ckeditor.com/contact/.',
    type: 'error',
    reactsTo: { message: 'license-key-usage-limit' }
  },
  {
    header: 'WProofreader Authorization Error',
    description: 'Some problems occurred during WProofreader initialization. Check the WProofreader plugin configuration.',
    type: 'error',
    reactsTo: { message: 'wproofreader-service-id-error' }
  },
  {
    header: 'WProofreader usage limit exceeded',
    description: 'The daily limit for the number of words checked using the WProofreader grammar and spell checker has been reached. Please contact your site administrator for help. Access to the service will resume at 00:00 UTC.',
    type: 'error',
    reactsTo: { message: 'wproofreader-usage-limit-error' }
  },
  {
    header: 'WProofreader Error',
    description: 'You have no permission to access the WProofreader proxy.',
    type: 'error',
    reactsTo: { message: 'wproofreader-permission-error' }
  },
  {
    header: 'Access denied',
    description: 'You don\'t have enough permissions for this action.',
    type: 'unhandledrejection',
    reactsTo: { message: 'You don\'t have enough permissions to access this resource' }
  }
]

class ErrorNotifications extends Plugin {
  constructor( ...args ) {
    super( ...args );

    this.availableNotifications = new Collection();
    this.activeNotification = null;
  }

  static get pluginName() {
    return 'ErrorNotifications'
  }

  init() {
    const editor = this.editor;

    this._setupNotifications( definitions );

    this.set( '_editable', null );

    editor.ui.once( 'ready', () => this.set( '_editable', editor.ui.view.editable.element ) );

    this._attachListeners();
  }

  destroy() {
    if (this.activeNotification) {
      this.activeNotification.hide();
      this.editor.ui.view.main.remove( this.activeNotification );
    }
    this.activeNotification = null;
    this._detachListeners();

    super.destroy();
  }

  _setupNotifications( definitions ) {
    for ( const definition of definitions ) {
      const notification = new NotificationView( this.editor.locale, definition );

      notification.bind( '_editable' ).to( this, '_editable' );

      notification.on( 'closeNotification', () => {
        notification.hide();

        this.activeNotification = null;
        this.editor.ui.view.main.remove( notification );

        this.editor.editing.view.focus();
      } );
      this.availableNotifications.add( notification )
    }
  }

  _attachListeners() {
    window.addEventListener( 'error', this._handleError.bind( this ) );
    window.addEventListener( 'unhandledrejection', this._handleError.bind( this ) );
  }

  _detachListeners() {
    window.removeEventListener( 'error', this._handleError.bind( this ) );
    window.removeEventListener( 'unhandledrejection', this._handleError.bind( this ) );
  }

  _handleError( evt ) {
    let notificationToShow = null;
    const matches = new Collection();

    if ( this.activeNotification ||
      ( evt.type === "error" && !evt.error) ||
      ( evt.type === "unhandledrejection" && !evt.reason)) {
      return;
    }

    for ( const notification of this.availableNotifications ) {
      const reactsTo = notification.reactsTo;

      for ( const key in reactsTo ) {
        if ( evt.type === "error" && evt.error[ key ] && evt.error[ key ].includes( reactsTo[ key ] ) ) {
          matches.add( notification );
        }
        if ( evt.type === "unhandledrejection" && evt.reason[ key ] && evt.reason[ key ].includes( reactsTo[ key ] ) ) {
          matches.add( notification );
        }
      }
    }

    // Notifications that react to the specific error message have higher priority
    if ( matches.length > 1 ) {
      notificationToShow = matches.find( notification => notification.reactsTo.message );
    } else {
      notificationToShow = matches.first;
    }

    if ( !notificationToShow ) {
      return;
    }

    this.activeNotification = notificationToShow;
    this.activeNotification.show();

    this.editor.ui.view.main.add( this.activeNotification );
  }
}

class NotificationView extends View {
  constructor( locale, definition ) {
    super( locale );

    this.reactsTo = definition.reactsTo;
    this.closeNotificationButton = null;

    this.set( '_editable', null );
    this.set( 'isVisible', false );
    this.set( 'positionBottom', '20px' );
    this.set( 'positionRight', '15px' );

    this.createTemplate( definition );

    this.render();

    this.on( 'change:isVisible', () => this._updateNotificationPosition() )

    this.listenTo( global.document, 'scroll', ( evt, data ) => {
      if ( this.isVisible ) {
        this._updateNotificationPosition();
      }
    } );
  }

  createTemplate( definition ) {
    const bind = this.bindTemplate;

    const notificationHeader = this._createNotificationHeader( definition.header, definition.type );
    const notificationDescription = this._createNotificationDescription( definition.description );
    const closeNotificationButton = this._createCloseNotificationButton();

    this.setTemplate( {
      tag: 'div',
      attributes: {
        class: [
          'ck-notification',
          `ck-notification__${ definition.type }`,
          bind.if( 'isVisible', 'ck-hidden', value => !value  )
        ],
        style: {
          position: 'absolute',
          bottom: bind.to( 'positionBottom' ),
          right: bind.to( 'positionRight' ),
          'z-index': 999
        }
      },
      children: [
        notificationHeader,
        notificationDescription,
        closeNotificationButton
      ]
    } )
  }

  show() {
    this.isVisible = true;
  }

  hide() {
    this.isVisible = false;
  }

  _createNotificationHeader( text, type ) {
    const view = new View();

    view.setTemplate( {
      tag: 'h4',
      attributes: {
        class: [
          'ck-notification__header',
          `ck-notification__header-${ type }`
        ]
      },
      children: [ text ]
    } )

    return view;
  }

  _createNotificationDescription( text ) {
    const view = new View();

    view.setTemplate( {
      tag: 'p',
      attributes: {
        class: [
          'ck-notification__description'
        ]
      },
      children: [ text ]
    } )

    return view;
  }

  _createCloseNotificationButton() {
    const view = new View();

    const bind = view.bindTemplate;

    view.setTemplate( {
      tag: 'span',
      attributes: {
        class: [
          'ck-notification__close'
        ]
      },
      children: [ 'x' ],
      on: {
        click: bind.to( evt => this.fire( 'closeNotification' ) )
      }
    } )

    return view;
  }

  _updateNotificationPosition() {
    const editable = this._editable;

    if ( !editable ) {
      return;
    }

    const editableRect = new Rect( editable );

    const visibleRect = editableRect.getVisible();

    const viewportHeight = window.innerHeight;
    const bottomBoundary = visibleRect.bottom - viewportHeight;
    const topBoundary = visibleRect.top;

    // Prevent sticking out of the editable.
    // viewportHeight - (notification height + margin)
    if ( topBoundary >= viewportHeight - 100 ) {
      return;
    }

    // If the editable's bottom boundary is invisible, stick the notification
    // to the viewport's position in the editable.
    if ( visibleRect.bottom > viewportHeight ) {
      this.set( 'positionBottom', `${ bottomBoundary + 20 }px` );
    } else {
      this.set( 'positionBottom', '20px' );
    }
  }
}

export default ErrorNotifications;
