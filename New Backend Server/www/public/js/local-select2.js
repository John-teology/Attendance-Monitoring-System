/* minimal offline select2-like enhancer */
(function($){
  $.fn.select2 = function(options){
    return this.each(function(){
      var $sel = $(this);
      if ($sel.data('localSelect2')) return;
      var parent = options && options.dropdownParent ? $(options.dropdownParent) : $sel.parent();
      var search = $('<input type="text" class="form-control mb-2" placeholder="Search...">');
      var chips = $('<div class="ls2-chips mb-2"></div>');
      $sel.before(chips);
      $sel.before(search);
      function renderChips(){
        var vals = $sel.val();
        vals = Array.isArray(vals) ? vals : (vals ? [vals] : []);
        chips.empty();
        vals.forEach(function(v){
          var txt = $sel.find('option[value="'+v+'"]').text();
          var chip = $('<span class="badge bg-light text-dark border me-1 mb-1"></span>').text(txt);
          var x = $('<button type="button" class="btn btn-sm btn-link text-danger ms-1 p-0">&times;</button>');
          x.on('click', function(){
            var current = $sel.val() || [];
            current = Array.isArray(current) ? current : [current];
            $sel.val(current.filter(function(id){ return (''+id) !== (''+v); })).trigger('change');
          });
          chip.append(x);
          chips.append(chip);
        });
      }
      function filterOptions(){
        var q = (search.val() || '').toLowerCase();
        $sel.find('option').each(function(){
          var t = $(this).text().toLowerCase();
          $(this).toggle(t.indexOf(q) !== -1);
        });
      }
      $sel.on('change.localselect2', renderChips);
      search.on('input.localselect2', filterOptions);
      renderChips();
      filterOptions();
      $sel.data('localSelect2', true);
    });
  };
})(jQuery);
