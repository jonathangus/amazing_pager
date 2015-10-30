(function ($, views) {
    Drupal.behaviors.amazing_pager = {
        attach: function (context) {
            var pagerData = drupalSettings.amazing_pager;

            function Pager() {
                this.inProgress = false;
                this.isEmpty = false;
                this.defaultSettings = {
                  row: 'views-row',
                  container: 'view-content',
                  loader: 'js-AmazingPager-spinner',
                  trigger: 'js-AmazingPager-trigger'
                };
                this.settings = $.extend(this.defaultSettings, pagerData.settings);
                this.$row = $('.' + this.settings.row);
                this.$container = $('.' + this.settings.container);
                this.$triggerElement = $('.' + this.settings.trigger);
                this.$loaderElement = $('.' + this.settings.loader);

                this.init();
            }

            Pager.prototype.init = function(){
                this.handler();
            }

            Pager.prototype.handler = function() {
                if (this.isEmpty || this.inProgress) return;

                if(pagerData.options.manual == 1) {
                    this.clickTrigger();
                }
                else {
                    this.scrollWatch();
                }
            }

            Pager.prototype.fetchMore = function() {
                var self = this;
                var offset = $('.' + this.settings.row).length;
                this.inProgress = true;
                this.$loaderElement.show();

                // Unbind scroll events untill we have appended all the items
                $(window).off('scroll', _scrollEvents);

                var url = '/amazing_pager/items/' + pagerData.view.name +
                          '?offset=' + offset +
                        '&args=' + pagerData.view.args;

                $.get(url , function(response) {
                    if(response.items && response.items.length > 0) {
                        self.appendResponse(response.items);
                    }

                    if(response.empty) {
                        self.isEmpty = true;
                        self.$triggerElement.hide();
                        self.$loaderElement.hide();
                    }
                });
            };

            Pager.prototype.appendResponse = function(content) {
                var self = this;

                if(pagerData.options.animate == 1) {
                    this.animateItems(content).then(function () {
                        self.inProgress = false;
                        self.handler();
                        self.$loaderElement.hide();
                    });
                }

                else {
                    $(this.settings.container).append(content);
                    this.inProgress = false;
                    this.$loaderElement.hide();
                    this.handler();
                }
            };

            Pager.prototype.animateItems = function(items) {
                var self = this;
                var deferred = $.Deferred();

                items.forEach(function(item, index) {
                    setTimeout(function() {
                        var $item = '<div class="' + self.settings.row + '">' + item + '</div>';
                        self.$container.append($item);
                        self.$container.find('.' + self.settings.row).last().addClass('animated ' + pagerData.options.animateEffect);

                        // Last of the array
                        if(index + 1 === items.length) {
                            // Return promise
                            deferred.resolve();
                        }

                    }, index * 100);

                });

                return deferred.promise();
            };

            _scrollEvents = function() {
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

                this.$triggerElement.html(views.manualText);

                this.$triggerElement.click(function() {
                    if(self.inProgress || self.isEmpty) return;
                    self.$triggerElement.html(views.loadText);
                    self.fetchMore();
                });
            }


           var wkse_pager =  new Pager();
        }
    };
})(jQuery, drupalSettings.amazing_pager);
