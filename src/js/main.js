import axios from "axios";
import Vue from "vue/dist/vue.esm";

const TransactionTimeoutTime = 60000;

let app = new Vue({
  el: '#app',
  data:{
      status: payment_text.status.intro,
      timerChecker: null,
      timeoutChecker: null,
      return_url: '#',
      btnDisabled: false,
      error: null,
      payment_text: payment_text
    },
  methods: {
    pay: function (data) {
      this.tooglePaymentButton();
      this.return_url = data.return_url;
      const params = new URLSearchParams();
      params.append('order_id', data.order_id);
      this.checkTimeout();
      this.status = payment_text.status.requested;

      axios.post('?wc-api=process_action', params).then(function (response) {
        if (response.data.status == 'success') {
          this.status = payment_text.status.received;
          setTimeout(() => (window.location.href = this.return_url), 5000);
        } else if (response.data.status == 'failed') {
          this.status = payment_text.status.failed;
          this.btnDisabled = false;
          clearInterval(this.timerChecker);
        }
      }.bind(this)).catch((error) => {
        this.status = payment_text.status.failed;
      })
    },
    tooglePaymentButton: function () {
      this.btnDisabled = (!this.btnDisabled)
    },
    checkTimeout: function () {
      this.timeoutChecker = setTimeout(() => {
        this.status = payment_text.status.timeout;
        clearInterval(this.timerChecker);
      }, TransactionTimeoutTime)
    }
  }
})
