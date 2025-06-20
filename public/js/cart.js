document.addEventListener('DOMContentLoaded', function() {
  // cart.phpから移動したコード
  const messageElement = document.querySelector('.cart-message');
  if (messageElement) {
    setTimeout(function() {
      messageElement.style.opacity = '0';
      setTimeout(function() {
        messageElement.remove();
      }, 500);
    }, 5000);
  }

});
// 支払い方法の説明を動的に変更する簡単なJavaScript
    const paymentDescriptions = {
      'bank_transfer': '<p>ご注文完了後、当店指定の口座へお振込みいただくデモです。</p><p>（実際のお振込みは不要です）</p>',
      'cash_on_delivery': '<p>商品お受け取り時に、配送業者へ現金でお支払いいただくデモです。</p><p>（実際のお支払いは不要です）</p>',
      'credit_card_demo': '<p>クレジットカード情報を入力する画面に進むデモです。</p><p>（実際のカード情報は入力しないでください）</p>',
      'paypay_demo': '<p>PayPayでお支払いいただく流れをシミュレートするデモです。</p><p>（実際のPayPay画面には遷移しません）</p>'
    };

    const paymentSelect = document.getElementById('payment_method_select');
    const descriptionArea = document.getElementById('payment_description_area');

    function updatePaymentDescription() {
      const selectedValue = paymentSelect.value;
      if (paymentDescriptions[selectedValue]) {
        descriptionArea.innerHTML = paymentDescriptions[selectedValue];
      } else {
        descriptionArea.innerHTML = '<p>お支払い方法を選択してください。</p>';
      }
}
    