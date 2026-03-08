(function (global, $) {
  if (!global.bootstrap) {
    global.bootstrap = {};
  }

  function closest(el, selector) {
    while (el && el.nodeType === 1) {
      if (el.matches && el.matches(selector)) return el;
      el = el.parentElement;
    }
    return null;
  }

  function getTargetSelector(trigger) {
    if (!trigger) return null;
    var target = trigger.getAttribute('data-bs-target');
    if (target) return target;
    var href = trigger.getAttribute('href');
    if (href && href.charAt(0) === '#') return href;
    return null;
  }

  function showBackdrop() {
    var bd = document.createElement('div');
    bd.className = 'modal-backdrop fade show';
    bd.setAttribute('data-compat-backdrop', '1');
    document.body.appendChild(bd);
    return bd;
  }

  function removeBackdrop() {
    var bds = document.querySelectorAll('[data-compat-backdrop="1"]');
    for (var i = 0; i < bds.length; i++) {
      bds[i].parentNode.removeChild(bds[i]);
    }
  }

  function Modal(element) {
    this._el = element;
  }

  Modal.prototype.show = function () {
    if (!this._el) return;
    this._el.style.display = 'block';
    this._el.removeAttribute('aria-hidden');
    this._el.setAttribute('aria-modal', 'true');
    this._el.setAttribute('role', 'dialog');
    this._el.className = this._el.className.replace(/\bshow\b/g, '').replace(/\s+/g, ' ').trim();
    this._el.className += ' show';
    document.body.className = (document.body.className + ' modal-open').replace(/\s+/g, ' ').trim();
    showBackdrop();
  };

  Modal.prototype.hide = function () {
    if (!this._el) return;
    this._el.className = this._el.className.replace(/\bshow\b/g, '').replace(/\s+/g, ' ').trim();
    this._el.style.display = 'none';
    this._el.setAttribute('aria-hidden', 'true');
    this._el.removeAttribute('aria-modal');
    document.body.className = document.body.className.replace(/\bmodal-open\b/g, '').replace(/\s+/g, ' ').trim();
    removeBackdrop();
  };

  function Tab(element) {
    this._el = element;
  }

  Tab.prototype.show = function () {
    if (!this._el) return;

    var nav = closest(this._el, '.nav');
    if (nav) {
      var activeLinks = nav.querySelectorAll('.nav-link.active');
      for (var i = 0; i < activeLinks.length; i++) {
        activeLinks[i].className = activeLinks[i].className.replace(/\bactive\b/g, '').replace(/\s+/g, ' ').trim();
      }
    }

    this._el.className = (this._el.className + ' active').replace(/\s+/g, ' ').trim();

    var selector = getTargetSelector(this._el);
    if (!selector) return;

    var pane = document.querySelector(selector);
    if (!pane) return;

    var container = closest(pane, '.tab-content');
    if (container) {
      var panes = container.querySelectorAll('.tab-pane');
      for (var j = 0; j < panes.length; j++) {
        panes[j].className = panes[j].className.replace(/\bshow\b/g, '').replace(/\bactive\b/g, '').replace(/\s+/g, ' ').trim();
      }
    }

    pane.className = (pane.className + ' show active').replace(/\s+/g, ' ').trim();
  };

  function Dropdown(element) {
    this._el = element;
  }

  function hasClass(el, cls) {
    return el && typeof el.className === 'string' && new RegExp('(^|\\s)' + cls + '(\\s|$)').test(el.className);
  }

  function addClass(el, cls) {
    if (!el || hasClass(el, cls)) return;
    el.className = (el.className + ' ' + cls).replace(/\s+/g, ' ').trim();
  }

  function removeClass(el, cls) {
    if (!el) return;
    el.className = el.className.replace(new RegExp('(^|\\s)' + cls + '(\\s|$)', 'g'), ' ').replace(/\s+/g, ' ').trim();
  }

  function getDropdownMenu(trigger) {
    if (!trigger) return null;
    var parent = closest(trigger, '.dropdown');
    if (parent) {
      var menu = parent.querySelector('.dropdown-menu');
      if (menu) return menu;
    }
    var next = trigger.nextElementSibling;
    if (next && hasClass(next, 'dropdown-menu')) return next;
    return null;
  }

  function closeAllDropdowns(exceptTrigger) {
    var shownMenus = document.querySelectorAll('.dropdown-menu.show');
    for (var i = 0; i < shownMenus.length; i++) {
      var m = shownMenus[i];
      removeClass(m, 'show');
      var dd = closest(m, '.dropdown');
      if (dd) {
        var t = dd.querySelector('[data-bs-toggle="dropdown"]');
        if (t && t !== exceptTrigger) {
          removeClass(t, 'show');
          t.setAttribute('aria-expanded', 'false');
        }
      }
    }
  }

  Dropdown.prototype.toggle = function () {
    if (!this._el) return;
    var menu = getDropdownMenu(this._el);
    if (!menu) return;

    var isOpen = hasClass(menu, 'show');
    closeAllDropdowns(this._el);

    if (isOpen) {
      removeClass(menu, 'show');
      removeClass(this._el, 'show');
      this._el.setAttribute('aria-expanded', 'false');
      return;
    }

    addClass(menu, 'show');
    addClass(this._el, 'show');
    this._el.setAttribute('aria-expanded', 'true');
  };

  function Toast(element, options) {
    this._el = element;
    this._timeout = null;
    options = options || {};
    this._delay = typeof options.delay === 'number' ? options.delay : null;
    this._autohide = typeof options.autohide === 'boolean' ? options.autohide : null;
  }

  Toast.prototype.show = function () {
    if (!this._el) return;
    var delayAttr = this._el.getAttribute('data-bs-delay');
    var autohideAttr = this._el.getAttribute('data-bs-autohide');

    var delay = this._delay !== null ? this._delay : (delayAttr ? parseInt(delayAttr, 10) : 5000);
    var autohide = this._autohide !== null ? this._autohide : (autohideAttr === null ? true : autohideAttr !== 'false');

    this._el.style.display = 'block';
    addClass(this._el, 'show');
    addClass(this._el, 'showing');

    var self = this;
    if (this._timeout) {
      clearTimeout(this._timeout);
      this._timeout = null;
    }

    setTimeout(function () {
      removeClass(self._el, 'showing');
      if (autohide) {
        self._timeout = setTimeout(function () {
          self.hide();
        }, delay);
      }
    }, 50);
  };

  Toast.prototype.hide = function () {
    if (!this._el) return;
    removeClass(this._el, 'show');
    removeClass(this._el, 'showing');
    this._el.style.display = 'none';
    if (this._timeout) {
      clearTimeout(this._timeout);
      this._timeout = null;
    }
  };

  global.bootstrap.Modal = Modal;
  global.bootstrap.Tab = Tab;
  global.bootstrap.Dropdown = Dropdown;
  global.bootstrap.Toast = Toast;

  function getModalInstance(modalEl) {
    if (!modalEl) return null;
    if (!modalEl.__compatModal) {
      modalEl.__compatModal = new Modal(modalEl);
    }
    return modalEl.__compatModal;
  }

  if ($ && $.fn) {
    $.fn.modal = function (action) {
      return this.each(function () {
        var inst = getModalInstance(this);
        if (!inst) return;
        if (action === 'show') inst.show();
        else if (action === 'hide') inst.hide();
      });
    };
  }

  document.addEventListener('click', function (e) {
    var target = e.target;

    var dismissBtn = closest(target, '[data-bs-dismiss="modal"]');
    if (dismissBtn) {
      var modalEl = closest(dismissBtn, '.modal');
      var inst = getModalInstance(modalEl);
      if (inst) inst.hide();
      e.preventDefault();
      return;
    }

    var dropdownTrigger = closest(target, '[data-bs-toggle="dropdown"]');
    if (dropdownTrigger) {
      new Dropdown(dropdownTrigger).toggle();
      e.preventDefault();
      return;
    }

    var tabTrigger = closest(target, '[data-bs-toggle="tab"]');
    if (tabTrigger) {
      new Tab(tabTrigger).show();
      e.preventDefault();
      return;
    }

    var modalTrigger = closest(target, '[data-bs-toggle="modal"]');
    if (modalTrigger) {
      var sel = getTargetSelector(modalTrigger);
      if (sel) {
        var m = document.querySelector(sel);
        var mi = getModalInstance(m);
        if (mi) mi.show();
      }
      e.preventDefault();
      return;
    }

    var backdrop = closest(target, '[data-compat-backdrop="1"]');
    if (backdrop) {
      var openModal = document.querySelector('.modal.show');
      var oi = getModalInstance(openModal);
      if (oi) oi.hide();
      e.preventDefault();
      return;
    }

    closeAllDropdowns(null);
  }, true);
})(window, window.jQuery);

