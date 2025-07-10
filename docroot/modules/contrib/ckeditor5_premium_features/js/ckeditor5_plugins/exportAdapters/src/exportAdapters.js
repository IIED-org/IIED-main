
/*
 * Copyright (c) 2003-2025, CKSource Holding sp. z o.o. All rights reserved.
 * For licensing, see https://ckeditor.com/legal/ckeditor-oss-license
 */

class ExportAdapters {
  constructor( editor ) {
    // Attach custom dataCallback when PDF export is enabled.
    if (editor.config._config.exportPdf && typeof editor.config._config.exportPdf !== 'undefined') {
      editor.config._config.exportPdf.dataCallback = (editor) => {
        return Drupal.CKEditor5PremiumFeatures.editorContentExportProcessor(
          editor, { enableHighlighting:true, convertImagesToBase64: editor.config._config.exportPdf.convertImagesToBase64 });
      }
    }

    // Attach custom dataCallback when Word export is enabled.
    if (editor.config._config.exportWord && typeof editor.config._config.exportWord !== 'undefined') {
      editor.config._config.exportWord.dataCallback = (editor) => {
        return Drupal.CKEditor5PremiumFeatures.editorContentExportProcessor(
          editor, { enableHighlighting: true, convertImagesToBase64: editor.config._config.exportWord.convertImagesToBase64 });
      }
    }
  }

  static get pluginName() {
    return 'ExportAdapters'
  }
}

export default ExportAdapters;
