<template>
  <div class="level51-autotranslateField">
    <a
      href=""
      @click.prevent="translate">
      translate from {{ payload.sourceLocale }}
    </a>
  </div>
</template>

<script>
import axios from 'axios';
import qs from 'qs';

/**
 * @todo proper styles, icons etc
 * @todo more functions?
 * @todo confirmation step to prevent accidental translations?
 * @todo error reporting
 * @todo show amount of characters (check how to count html content?)
 * @todo i18n
 */
export default {
  props: {
    payload: {
      type: Object,
      required: true
    }
  },
  data() {
    return {
      inputElement: null
    };
  },
  mounted() {
    this.inputElement = document.getElementById(this.payload.id);
  },
  computed: {
    endpoint() {
      const endpoint = this.payload.googleTranslateApi;

      let params = {};

      if (this.payload.getVars && typeof this.payload.getVars === 'object') {
        params = {
          ...this.payload.getVars
        };
      }

      return `${endpoint}?${qs.stringify(params, { encode: true })}`;
    }
  },
  methods: {
    async translate() {
      await this.translateWithGoogle();
    },
    async translateWithGoogle() {
      try {
        const response = (await axios.post(this.endpoint, {
          source: this.payload.sourceLocale,
          target: this.payload.targetLocale,
          q: [this.payload.sourceValue]
        })).data;

        if (response.data
          && response.data.translations
          && Array.isArray(response.data.translations)
          && response.data.translations.length > 0) {
          this.setValue(response.data.translations[0].translatedText);
        }
      } catch (error) {
        console.log(error);
      }
    },
    setValue(value) {
      const type = this.inputElement.constructor.name;

      if (type === 'HTMLTextAreaElement') {
        if (this.inputElement.dataset && this.inputElement.dataset.editor && this.inputElement.dataset.editor === 'tinyMCE') {
          // eslint-disable-next-line no-undef
          tinymce.get(this.inputElement.id).setContent(value);
        } else {
          this.inputElement.innerHTML = value;
        }
      } else {
        this.inputElement.value = value;
      }
    }
  }
};
</script>

<style lang="less">
.level51-autotranslateField {
  margin-top: 6px;
}
</style>
