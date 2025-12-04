(function (Drupal) {
  /**
   * Attaches the JS.
   */
  Drupal.behaviors.TaxonomyManagerMergeTree = {
    attach(context, settings) {
      document.addEventListener(
        'taxonomy_manager-tree-select',
        function (event) {
          const data = event.detail || {};

          if (data.tree.getSelectedNodes().length < 1) {
            // Disable the button if less the two terms are selected.
            document.getElementById('edit-merge').disabled = true;
          } else {
            const mergeButton = document.getElementById('edit-merge');
            mergeButton.disabled = false;
            if (mergeButton.classList.contains('is-disabled')) {
              mergeButton.classList.remove('is-disabled');
            }
          }
        },
      );
    },
  };
})(Drupal);
