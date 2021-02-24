import Vue from 'vue';
import AjaxSelectField from 'src/App.vue';
import VueI18n from 'vue-i18n';
import en from '../lang/en.json';
import de from '../lang/de.json';
import watchElement from './util';

const render = (el) => {
  Vue.use(VueI18n);

  const payload = JSON.parse(el.dataset.payload);

  const i18n = new VueI18n({
    locale: payload.cmsLocale,
    fallbackLocale: 'en',
    messages: { en, de }
  });

  new Vue({
    i18n,
    render(h) {
      return h(AjaxSelectField, {
        props: {
          payload
        }
      });
    }
  }).$mount(`#${el.id}`);
};

watchElement('.level51-autotranslateFieldPlaceholder', (el) => {
  setTimeout(() => {
    render(el);
  }, 1);
});
