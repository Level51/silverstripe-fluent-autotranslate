<template>
  <div>
    <div class="level51-modal modal fade show">
      <div class="modal-dialog">
        <div class="modal-content">
          <div class="modal-header">
            <h5
              v-if="title"
              class="modal-title"
              v-html="title" />
            <button
              type="button"
              class="close"
              @click.prevent="close">
              <span aria-hidden="true">x</span>
            </button>
          </div>

          <slot />
        </div>
      </div>
    </div>
    <div class="modal-backdrop fade show" />
  </div>
</template>

<script>
export default {
  props: {
    title: {
      type: String,
      required: false,
      default: null
    }
  },
  data() {
    return {
      containerClass: 'level51-modal'
    };
  },
  mounted() {
    window.addEventListener('keyup', this.handleKeyup, false);
    window.addEventListener('click', this.handleClick, false);

    this.storeOffset();
    document.getElementsByTagName('body')[0].classList.add('level51-overflow--hidden');
  },
  beforeDestroy() {
    window.removeEventListener('keyup', this.handleKeyup, false);
    window.removeEventListener('click', this.handleClick, false);

    document.getElementsByTagName('body')[0].classList.remove('level51-overflow--hidden');

    this.restoreOffset();
  },
  methods: {
    close() {
      this.$emit('close');
    },
    handleKeyup(e) {
      if (e.target.nodeName === 'INPUT') return;
      if (e.keyCode === 27) {
        this.close();
      }
    },
    handleClick(e) {
      if (e.target
        && e.target.classList
        && typeof e.target.classList.contains === 'function'
        && e.target.classList.contains(this.containerClass)) {
        this.close();
      }
    },
    storeOffset() {
      document.body.style.top = `-${window.pageYOffset}px`;
    },
    restoreOffset() {
      const offset = document.body.style.top;
      document.body.style.top = '';
      window.scrollTo(0, parseInt(offset || '0') * -1);
    }
  }
};
</script>

<style lang="less">
@import (reference) '../styles/vars';

.modal.level51-modal {
  display: block;
}
</style>
