import VideoEmbedEditing from './videoembedediting';
import VideoEmbedUI from './videoembedui';
import { Plugin } from 'ckeditor5/src/core';

export default class VideoEmbed extends Plugin {

static get requires() {
    return [VideoEmbedEditing, VideoEmbedUI];
  }
}