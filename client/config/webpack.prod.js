/* eslint-disable import/no-extraneous-dependencies */
const path = require('path');
const { VueLoaderPlugin } = require('vue-loader');
const { DefinePlugin } = require('webpack');
const MiniCSSExtractPlugin = require('mini-css-extract-plugin');
const CompressionPlugin = require('compression-webpack-plugin');
const CssMinimizerPlugin = require('css-minimizer-webpack-plugin');

const resolve = require('./webpack.resolve').forWebpack;

module.exports = (env = {}) => ({
  entry: {
    autotranslateField: ['core-js/stable', 'regenerator-runtime/runtime', 'src/autotranslateField.js'],
  },

  output: {
    path: path.resolve(__dirname, '../dist'),
    filename: '[name].js',
    publicPath: '',
  },

  mode: env.NODE_ENV || 'production',

  resolve,

  module: {
    rules: [
      {
        test: /\.js$/,
        exclude: /node_modules/,
        use: {
          loader: 'babel-loader',
          options: {
            presets: ['@babel/preset-env'],
            plugins: ['@babel/plugin-proposal-object-rest-spread'],
          },
        },
      },
      {
        test: /\.vue$/,
        loader: 'vue-loader',
      },
      {
        test: /\.css$/,
        use: [
          MiniCSSExtractPlugin.loader,
          'css-loader',
        ],
      },
      {
        test: /\.less$/,
        use: [
          MiniCSSExtractPlugin.loader,
          'css-loader',
          'less-loader',
        ],
      },
    ],
  },

  optimization: {
    minimize: true,
    minimizer: [
      new CssMinimizerPlugin(), // Replaced OptimizeCssAssetsPlugin
    ],
  },

  plugins: [
    new DefinePlugin({
      'process.env.NODE_ENV': JSON.stringify(env.NODE_ENV || 'production'),
    }),

    new VueLoaderPlugin(),

    new MiniCSSExtractPlugin({
      filename: '[name].css',
    }),

    new CompressionPlugin({
      algorithm: 'gzip',
      test: /\.(js|css|html|svg)$/,
    }),
  ],
});
