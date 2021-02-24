<template>
  <div class="level51-autotranslateField">
    <a
      href=""
      @click.prevent="isTranslateModalVisible = true">
      <i class="font-icon font-icon-translatable" />
      {{ ctaLabel }}
    </a>

    <modal
      v-if="isTranslateModalVisible"
      @close="isTranslateModalVisible = false"
      :title="payload.fieldTitle">
      <div class="modal-body">
        <h2>
          {{ modalHeadline }}
        </h2>

        <h3>
          {{ modalSourceValueLabel }}
        </h3>

        <div
          class="level51-autotranslateField-sourceValue"
          v-html="sourceValue" />

        <div
          class="level51-pricingHint"
          v-html="$t(`${provider}.pricingHint`, { charCount, targetLocale: targetLocale.title })" />
      </div>

      <div class="modal-footer">
        <a
          class="btn btn-primary font-icon-translatable"
          href=""
          @click.prevent="translate">
          {{ $t('modal.translateCta') }}
        </a>
      </div>
    </modal>
  </div>
</template>

<script>
import axios from 'axios';
import qs from 'qs';
import Modal from './Modal.vue';

/**
 * @todo error reporting
 */
export default {
  components: { Modal },
  props: {
    payload: {
      type: Object,
      required: true
    }
  },
  data() {
    return {
      inputElement: null,
      isTranslateModalVisible: false
    };
  },
  mounted() {
    this.inputElement = document.getElementById(this.inputId);
  },
  computed: {
    inputId() {
      return this.payload.id;
    },
    provider() {
      return this.payload.provider;
    },
    providerConfig() {
      return this.payload.providerConfig;
    },
    endpoint() {
      const endpoint = this.providerConfig.apiEndpoint;

      let params = {};

      if (this.payload.getVars && typeof this.payload.getVars === 'object') {
        params = {
          ...this.payload.getVars
        };
      }

      if (this.providerConfig.getVars && typeof this.providerConfig.getVars === 'object') {
        params = {
          ...this.providerConfig.getVars
        };
      }

      return `${endpoint}?${qs.stringify(params, { encode: true })}`;
    },
    sourceLocale() {
      return this.payload.sourceLocale;
    },
    targetLocale() {
      return this.payload.targetLocale;
    },
    sourceValue() {
      return this.payload.sourceValue;
    },
    charCount() {
      return this.payload.sourceValue.length;
    },
    ctaLabel() {
      return this.$t('field.translateCta', {
        sourceLocale: this.sourceLocale.title,
        targetLocale: this.targetLocale.title
      });
    },
    modalTitle() {
      return this.$t('modal.headline', {
        sourceLocale: this.sourceLocale.title,
        targetLocale: this.targetLocale.title
      });
    },
    modalHeadline() {
      return this.$t('modal.headline', {
        sourceLocale: `${this.sourceLocale.title} (${this.sourceLocale.locale})`,
        targetLocale: `${this.targetLocale.title} (${this.targetLocale.locale})`
      });
    },
    modalSourceValueLabel() {
      return this.$t('modal.sourceValueLabel', {
        locale: this.sourceLocale.title
      });
    }
  },
  methods: {
    async translate() {
      await this.translateWithGoogle();
      this.isTranslateModalVisible = false;
    },
    async translateWithGoogle() {
      try {
        const response = (await axios.post(this.endpoint, {
          source: this.sourceLocale.code,
          target: this.targetLocale.code,
          q: [this.sourceValue]
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
@import (reference) '../styles/vars';

.level51-autotranslateField {
  margin-top: @space-1;

  .font-icon-translatable {
    color: @color-cta;
  }

  .level51-modal {
    h3 {
      margin-bottom: @space-2;
    }

    .level51-autotranslateField-sourceValue {
      padding: @space-2;
      background: @color-mono-94;
      margin-bottom: @space-3;

      p {
        &:first-child {
          margin-top: 0;
        }

        &:last-child {
          margin-bottom: 0;
        }
      }
    }

    .level51-pricingHint {
      p {
        &:first-child {
          margin-top: 0;
        }

        &:last-child {
          margin-bottom: 0;
        }
      }
    }
  }
}
</style>
