/*
 * Copyright (c) 2003-2025, CKSource Holding sp. z o.o. All rights reserved.
 * For licensing, see https://ckeditor.com/legal/ckeditor-oss-license
 */

class AIServiceAdapter {

  static get pluginName() {
    return 'AIServiceAdapter'
  }

  constructor( editor ) {
    this.editor = editor;
    const AIAdapter = this.editor.plugins.get('AIAdapter');
    let textAdapter;
    if (this.editor.config._config.ai.textAdapter === 'aws') {
      textAdapter = editor.plugins.get('AWSTextAdapter');
    } else {
      textAdapter = editor.plugins.get('OpenAITextAdapter');
    }
    AIAdapter.set('textAdapter', textAdapter)
  }
}
export default AIServiceAdapter
