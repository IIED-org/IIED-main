/*
 * Copyright (c) 2003-2025, CKSource Holding sp. z o.o. All rights reserved.
 * For licensing, see https://ckeditor.com/legal/ckeditor-oss-license
 */

import path from 'path';
import glob from 'glob';
import webpack from 'webpack';
import { styles, builds } from '@ckeditor/ckeditor5-dev-utils';
import TerserPlugin from 'terser-webpack-plugin';
import manifest from './node_modules/ckeditor5/build/ckeditor5-dll.manifest.json' assert { type: 'json' };

const entries = glob.sync('./{modules/*/js,js}/ckeditor5_plugins/**/*.js').reduce((entries, entry) => {
  const entryName = path.parse(entry).name;
  if (entryName !== 'index') {
    entries[entryName] = entry.replace(entryName + '/src/' + entryName + '.js', '');
  }
  return entries;
}, {});

const configs = [];
Object.entries(entries).forEach((mapping) => {
  const name = mapping[0];
  const dir = mapping[1];

  const bc = {
    mode: 'production',
    optimization: {
      minimize: true,
      minimizer: [
        new TerserPlugin({
          terserOptions: {
            output: {
              comments: /^!/
            }
          },
          test: /\.js(\?.*)?$/i,
          extractComments: false,
        }),
      ],
      moduleIds: 'named',
    },
    entry: {
      path: dir + `${name}/src/index.js`,
    },
    output: {
      path: path.resolve(dir, '../build'),
      filename: `${name}.js`,
      library: ['CKEditor5', name],
      libraryTarget: 'umd',
      libraryExport: 'default',
    },
    plugins: [
      new webpack.BannerPlugin("Copyright (c) 2003-2025, CKSource Holding sp. z o.o. All rights reserved.\nFor licensing, see https://ckeditor.com/legal/ckeditor-oss-license"),
      new webpack.DllReferencePlugin({
        manifest: manifest,
        scope: 'ckeditor5/src',
        name: 'CKEditor5.dll',
      }),
    ],
    module: {
      rules: [
        { test: /\.svg$/, use: 'raw-loader' },
        { test: /\.css$/, use: ['style-loader', 'css-loader'] }
      ],
    },
  };

  configs.push(bc);
});

export default configs;
