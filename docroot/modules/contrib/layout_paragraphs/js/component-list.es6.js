(($, Drupal) => {
  Drupal.behaviors.layoutParagraphsComponentList = {
    attach: function attach(context) {
      $('.lpb-component-list-search-input', context).keyup((e) => {
        const v = e.currentTarget.value;
        const pattern = new RegExp(v, 'i');
        const $list = $(e.currentTarget)
          .closest('.lpb-component-list')
          .find('.lpb-component-list__item');
        $list.each((i, item) => {
          if (pattern.test(item.innerText)) {
            item.removeAttribute('style');
          } else {
            item.style.display = 'none';
          }
        });
      });
    },
  };
})(jQuery, Drupal);
