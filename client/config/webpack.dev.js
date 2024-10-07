/* eslint-disable import/no-extraneous-dependencies */
const path = require('path');
const { VueLoaderPlugin } = require('vue-loader');
const { DefinePlugin } = require('webpack');
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

  mode: env.NODE_ENV || 'development',

  devtool: 'eval-cheap-module-source-map', // Updated to Webpack 5 compatible devtool

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
          'vue-style-loader',  // Hot-reloads CSS during development
          'css-loader',
        ],
      },
      {
        test: /\.less$/,
        use: [
          'vue-style-loader',  // Hot-reloads LESS during development
          'css-loader',
          'less-loader',
        ],
      },
    ],
  },

  plugins: [
    new DefinePlugin({
      'process.env.NODE_ENV': JSON.stringify(env.NODE_ENV || 'development'),
    }),

    new VueLoaderPlugin(),

    // No MiniCSSExtractPlugin in development to allow hot-reloading of styles
  ],

  devServer: {
    contentBase: path.join(__dirname, 'dist'),
    hot: true,  // Enable hot module replacement
    open: true, // Automatically open the app in the browser
  },
});
