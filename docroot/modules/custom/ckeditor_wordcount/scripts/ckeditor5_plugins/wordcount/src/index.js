import { Plugin } from 'ckeditor5/src/core';
import { WordCount } from '@ckeditor/ckeditor5-word-count';
import { StatusView } from './status_view';

class DrupalWordCount extends Plugin {

    init() {
        const editor = this.editor;
        const wordCount=editor.plugins.get('WordCount');
        const statusView = new StatusView();
        //editor.ui.view.main.add(statusView);

       editor.ui.on('ready',
            ()=>{
              editor.ui.view.main.add(statusView);
              wordCount.on('update', (ev,stats)=>{
                  let wordsElement=statusView.element.querySelector('.ckeditor-count-words');
                  let charsElement=statusView.element.querySelector('.ckeditor-count-characters');
                  wordsElement.textContent=stats.words;
                  charsElement.textContent=stats.characters;
              });
            }
        );

    }

    static get pluginName() {
        return 'DrupalWordCount';
    }
}

export default {WordCount,DrupalWordCount};
