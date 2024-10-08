(function ($, Drupal) {
  Drupal.behaviors.BidasoaKeyValue = {
    attach: function attach(context) {
      $(once('bidasoa_keyvalue.search', '.form-element--type-search', context)).each(function () {
        let keyword =  $(this).val();
        $('tbody tr').each(function(element, index){
          var self = $(this);
          var col_1_value = self.find("td:eq(0)").text().trim();
          var col_2_value = self.find("td:eq(1)").text().trim();
          if((col_1_value.search(keyword) > -1) || (col_2_value.search(keyword) > -1)) {
            $(this).show();
          } else {
            $(this).hide();
          }
        });

      })
    }
  };

})(jQuery, Drupal);
