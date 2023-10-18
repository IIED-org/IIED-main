import { View } from 'ckeditor5/src/ui';

export class StatusView extends View {

    constructor( locale ) {
        super( locale );

        this.setTemplate( {
            tag: 'div',
            children: [
                {
                    tag: 'span',
                    attributes : {class: ['ckeditor-count-words'], title: 'Words'}
                },
                {
                    tag: 'span',
                    attributes : {class: ['ckeditor-count-characters'], title: 'Characters'}
                }
            ],
            attributes: {
                class: [
                    'ckeditor-wordcount-container',
                ]
            }
        });
    }
}
