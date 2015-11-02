(function ($, views) {
  var pagerData = drupalSettings.amazing_pager;

  function Pager() {
    this.inProgress = false;
    this.isEmpty = false;
    this.loadedOneTime = false;
    this.defaultSettings = {
      row: 'views-row',
      loader: 'js-AmazingPager-spinner',
      trigger: 'js-AmazingPager-trigger',
    };
    this.settings = $.extend(this.defaultSettings, pagerData.settings);
    this.$row = $('.' + this.settings.row);
    this.$container = $('.' + this.settings.container);
    this.init();
  }

  Pager.prototype.init = function() {
    this.setLoader();
    this.setTriggerElement();
    this.handler();
  }

  Pager.prototype.setLoader = function() {
    this.$loaderElement = $('.' + this.settings.loader);
  }

  Pager.prototype.setTriggerElement = function() {
    this.$triggerElement = $('.' + this.settings.trigger);
  }

  Pager.prototype.handler = function() {
    if (this.isEmpty || this.inProgress) return;

    if(pagerData.options.type === 'infinite_scroll') {
      this.scrollWatch();
    }
    else if(pagerData.options.type === 'click_scroll' && this.loadedOneTime ) {
      this.$triggerElement.hide();
      this.scrollWatch();
    }
    else {
      this.clickTrigger();
    }
  }

  Pager.prototype.fetchMore = function() {
    var self = this;
    var offset = $('.' + this.settings.row).length;
    this.inProgress = true;
    this.$loaderElement.show();

    // Unbind scroll events untill we have appended all the items
    $(window).off('scroll', _scrollEvents);
    var args = pagerData.view.args.length > 0 ? '&args=' + pagerData.view.args : '';
    var url = '/amazing_pager/items/' + pagerData.view.name + '?offset=' + offset + args

    $.get(url , function(response) {
      if(response.items && response.items.length > 0) {
        self.loadedOneTime = true;
        self.appendResponse(response.items);
      }

      if(response.empty) {
        self.isEmpty = true;
        self.$triggerElement.hide();
        self.$loaderElement.hide();
      }
    });
  };

  Pager.prototype.completeAppend = function() {
    this.inProgress = false;
    this.handler();
    this.$loaderElement.hide();

    if(!this.isEmpty && pagerData.options.type === 'manual_load') {
      this.$triggerElement.show();
    }
  }

  Pager.prototype.appendResponse = function(content) {
    var self = this;
    if(pagerData.animate) {
      this.animateItems(content).then(function () {
        self.completeAppend();
      });
    }

    else {
      $(this.settings.container).append(content);
      this.completeAppend();
    }
  };

  Pager.prototype.animateItems = function(items) {
    var self = this;
    var deferred = $.Deferred();

    items.forEach(function(item, index) {
      setTimeout(function() {
        var $lastRow = $('.' + self.settings.row).last();
        var $item = '<div ' + index + ' class="' + self.settings.row + '">' + item + '</div>';

        $lastRow.after($item);
        $('.' + self.settings.row).last().addClass('animated ' + pagerData.animate.animateEffect);

        // Last of the array
        if(index + 1 === items.length) {
          // Return promise
          deferred.resolve();
        }

      }, index * 100);

    });

    return deferred.promise();
  };

  var _scrollEvents = function() {
    var docHeight = $(document).height();
    var winScroll = $(window).scrollTop();
    var winHeight = $(window).height();

    if(docHeight < (winScroll + winHeight + 500)) {
      wkse_pager.fetchMore();
    }
  }

  Pager.prototype.scrollWatch = function() {
    $(window).scroll(_scrollEvents);
  };

  Pager.prototype.clickTrigger = function() {
    var self = this;

    this.$triggerElement.click(function() {
      if(self.inProgress || self.isEmpty) return;
      self.$triggerElement.hide();
      self.fetchMore();
    });
  }


 var wkse_pager =  new Pager();

})(jQuery, drupalSettings.amazing_pager);
