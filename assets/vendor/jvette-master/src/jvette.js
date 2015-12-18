var jVette = (function($, w, d, undefined)
{
    'use strict';
    
    var jv
       ,defaults = {
            disabled: false
           ,transforms: true
           ,slide: {
                easing: 'swing'
               ,duration: 350 
            }
           ,swipe: {
               threshold: 10
              ,allowPageScroll: 'vertical'                    
            }
        };
        
    w = $(w);
    d = $(d);
    
    function jVette(options)
    {
        if (jv instanceof jVette)
            return jv;

        if (!(this instanceof jVette)) {
            if ('undefined' === typeof vette) {
                jv = new jVette(options);
            }
            return jv;
        }

        var  self = $(this)
            ,opts = $.extend(true, {}, defaults, options)
            ,enabled = !opts.disabled
            ,locked = false
            ,loaded = false  
            ,scroll = 0
            ,height = 0
            ,width = 0
            ,touch = !!('ontouchstart' in w.get(0))
            ,webkit = !!('WebkitAppearance' in d.get(0).documentElement.style)
            ,resize = {}
            ,transforms = false
            ,prefix = ['-webkit-','-moz-', '-ms-', '-o-', '']
            ,timer
            ,animating = false
            ,b1 = $('body:last')
            ,b2 = $(d.get(0).createElement('body'))
            ,h = $('html')
            ,p = $('[data-jv-panel]')
            ,t = $('[data-jv-trigger]')
            ,c = $('[data-jv-content]')
            ,f = $('.jv-fixed')
            ,i = $('.jv-panel, .jv-ignore');
                
        b2.get(0).setAttribute('id', 'jvette');
        b1.length && b2.insertAfter(b1);        
                
        ;(function()
        {
            var div = $('<div />');
            div.attr('style', 'transition:top 1s ease;-webkit-transition:top 1s ease;-moz-transition:top 1s ease;-o-transition:top 1s ease;');
            b2.append(div);
            transforms = !!(div.get(0).style.transition || div.get(0).style.webkitTransition || div.get(0).style.MozTransition || div.get(0).style.OTransitionDuration);
            if (!transforms)
                opts.transforms = false;
            div.remove();
        })();
        
        var D = 0x0000
           ,L = 0x0001
           ,R = 0x0002
           ,C = 0x0004;
           
        var resize = function()
        {
            if (!(D & (L | R)) || b1.is(':animated'))
                return; 
                                    
            var delta = w.width() - resize.width
               ,offset = resize.offset
               ,css = {};

            switch (true) {
                case 0 !== (D & R):
                        var value = width + delta;
                        css = true === opts.transforms ? transform(value, 'width') : { width: value };
                    break; 
               
                default:
                    if (delta <= 0) {
                        css = true === opts.transforms ? transform(offset += delta) : { left: delta };
                    }
                    break;
            };
            
            b1.css(css);
        };
                   
        w.on('resize orientationchange', function(e)
        {
            timer = clearTimeout(timer);
            
            if ('orientationchange' === e.type) {
                timer = setTimeout(function()
                {
                    resize();
                }, 100);
            } else {
                resize();
            }
        });
       
        w.scroll(function(e)
        {
            timer = clearTimeout(timer);
            timer = setTimeout(function()
            {
                timer = clearTimeout(timer);
                if (!h.hasClass('jv-open') &&
                    !h.hasClass('jv-opened'))
                    scroll = w.scrollTop();
            }, 15);
        });
        
        self.on('closed', function(e)
        {
            $('.jv-content', e.panel).css('display', '');
        });
        
        var lock = function(e)
        {
            if ('touchmove' === e.type)
                e.stopPropagation();
            
            locked = true;
        };

        var unlock = function(e)
        {
            locked = false;
        };        
        
        // http://easings.net/
        // http://api.jqueryui.com/easings/
        var easing = function(eqn)
        {
            switch (eqn) {
                
                case 'easeInQuad':
                    eqn = [0.55, 0.085, 0.68, 0.53];
                    break;
                    
                case 'easeOutQuad':
                    eqn = [0.25, 0.46, 0.45, 0.94];
                    break;
                    
                case 'easeInOutQuad':
                    eqn = [0.455, 0.03, 0.515, 0.955];
                    break;
                    
                case 'easeInCubic':
                    eqn = [0.55, 0.055, 0.675, 0.19];
                    break;
                    
                case 'easeOutCubic':
                    eqn = [0.215, 0.61, 0.355, 1];
                    break;
                    
                case 'easeInOutCubic':
                    eqn = [0.645, 0.045, 0.355, 1];
                    break;
                
                case 'easeInQuart':
                    eqn = [0.895, 0.03, 0.685, 0.22];
                    break;
                    
                case 'easeOutQuart':
                    eqn = [0.165, 0.84, 0.44, 1];
                    break;
                    
                case 'easeInOutQuart':
                    eqn = [0.77, 0, 0.175, 1];
                    break;
                    
                case 'easeInQuint':    
                    eqn = [0.755, 0.05, 0.855, 0.06];
                    break;
                    
                case 'easeOutQuint':
                    eqn = [0.23, 1, 0.32, 1];
                    break;
                    
                case 'easeInOutQuint':
                    eqn = [0.86, 0, 0.07, 1];
                    break;
                    
                case 'easeInExpo':
                    eqn = [0.95, 0.05, 0.795, 0.035];
                    break;
                    
                case 'easeOutExpo':
                    eqn = [0.19, 1, 0.22, 1];
                    break;
                    
                case 'easeInOutExpo':
                    eqn = [1, 0, 0, 1];
                    break;
                    
                case 'easeInSine':
                    eqn = [0.47, 0, 0.745, 0.715];
                    break;
                    
                case 'easeOutSine':
                    eqn = [0.39, 0.575, 0.565, 1];
                    break;
                    
                case 'easeInOutSine':
                    eqn = [0.445, 0.05, 0.55, 0.95];
                    break;
                    
                case 'easeInCirc':
                    eqn = [0.6, 0.04, 0.98, 0.335];
                    break;
                    
                case 'easeOutCirc':
                    eqn = [0.075, 0.82, 0.165, 1];
                    break;
                    
                case 'easeInOutCirc':
                    eqn = [0.785, 0.135, 0.15, 0.86];
                    break;
                    
                case 'easeInBack':
                    eqn = [0.6, -0.28, 0.735, 0.045];
                    break;
                    
                case 'easeOutBack':
                    eqn = [0.175, 0.885, 0.32, 1.275];                    
                    break;
                    
                case 'easeInOutBack':
                    eqn = [0.68, -0.55, 0.265, 1.55];
                    break;

                case 'swing':
                    eqn = [.02, .01, .47, 1];
                    break;
                    
                case 'linear':
                    return eqn;
                    
                default:           
                    return null;
            };
            
            return 'cubic-bezier(' + eqn.join(',') + ')';            
        };
        
        var transform = function(value, prop)
        {
            var css = {}
               ,prop = prop || 'transform';
            
            value = parseInt(value);
            
            switch (prop.toLowerCase()) {
              
                case 'width':
                    css['transition'] = prop + ' ' + opts.slide.duration + 'ms ' + easing(opts.slide.easing);   
                    css['width'] = value; 
                    break;
                    
                case 'transform':
                    $.each(prefix, function(i, v)
                    {
                        css[v + 'transition'] = v + prop + ' ' + opts.slide.duration + 'ms ' + easing(opts.slide.easing);                
                        css[v + 'transform'] = 'translateX(' + value + 'px)';                
                    });
                    break;
            };
            
            return css;           
        };
        
        var complete = function(e)
        {
            timer = clearTimeout(timer);

            resize.width = w.width();
            resize.offset = b1.offset().left;            
            resize.panel = D & L ? p.first() : p.last();
            resize.ratio = resize.panel.width() / resize.width;
            
            switch (true) {

                case 0 !== (D & L):
                    b1.css('right', 'auto');
                    h.addClass('jv-opened jv-opened-left')
                      .removeClass('jv-open jv-open-left');                    
                    break;

                case 0 !== (D & R):
                    b1.css('left', 'auto');
                    h.addClass('jv-opened jv-opened-right')
                      .removeClass('jv-open jv-open-right');                    
                    break;

                default:

                    h.removeClass('jv-opened jv-opened-right jv-opened-left');
                    w.scrollTop(scroll);

                    var css = {};

                    if (true === opts.transforms) {
                        $.each(prefix, function(i, v)
                        {
                            css[v + 'transition'] = '';
                            css[v + 'transform'] = '';
                        });
                    }

                    css.width = '';
                    css.height = '';
                    
                    b1.css(css);                    
                    h.css('height', '');
                    
                    f.each(function()
                    {
                        $(this).css({top: '', position: ''}); 
                    });
                    
                    D = 0;
                    
                    break;
            }

            var type = (!(D & (L | R)) ? 'closed' : 'opened');

            if (e instanceof jVette.SwipeEvent)
                e = new jVette.SwipeEvent(type, e);
            else 
                e = new jVette.TriggerEvent(type, e);

            self.triggerHandler(e);            
        };
        
        var click = function(e)
        {
            if (!enabled || b2.is(':animated') || animating)
                e.preventDefault();
        };
        
        var trigger = function(e)
        {
            if (!enabled || b2.is(':animated') || animating || e.defaultPrevented)
                return;
            
            var trig = $(this)
               ,active = trig.hasClass('jv-active');
            
            t.filter('[data-jv-trigger="' + trig.data('jv-trigger') + '"]').removeClass('jv-active');
            
            if (!active)
                trig.addClass('jv-active');
            
            e.preventDefault();

            e = new jVette.TriggerEvent();
            e.target = this;
            e.relatedTarget = b1.get(0);

            var dir = trig.data('jv-trigger')
               ,idx = trig.data('jv-show');

            if (!active && 'undefined' !== typeof idx) {
                

                idx = $.map(idx.toString().split(','), function(n)
                {
                    return parseInt(n);
                });
                
                if (idx.length) {
                    
                    var contents = $('.jv-content', e.panel).hide(0)
                       ,content = null;

                    $.each(idx, function(i,v)
                    {
                        content = $(contents.get(v));

                        if (content.length) {
                            e.type = 'changed';
                            content.css('display', '');
                        }
                    });
                }
            }

            switch (true) {

                case /^c/i.test(dir):
                    jv.close();
                    break;

                case ((D & L) && /^l/i.test(dir)) && 'changed' === e.type:
                case ((D & R) && /^r/i.test(dir)) && 'changed' === e.type:
                    e.direction = !(D & L) ? 'right' : 'left';
                    e.panel = !(D & L) ? p.last() : p.first();
                    self.trigger(e);
                    break;
                    
                case /^r/i.test(dir):
                    split(R, e);
                    break;

                case /^l/i.test(dir):
                default:
                    split(L, e);
                    break;
            };
        };
        
        var split = function(dir, e)
        {
            if (b1.is(':animated') || animating)
                return;
            
            d.get(0).getSelection().removeAllRanges();

            e.type = (D & (L | R) ? 'close' : 'open');            
            e.direction = (dir & L) ? 'left' : 'right';
            e.panel = (dir & L) ? p.first() : p.last();
            e.view = {};
            e.view.height = height;
            e.view.width = width;
            e.view.scroll = scroll;
            
            self[0]['on' + e.type] = function(e)
            {                
                if (e.isDefaultPrevented()) {
                    'open' === e.type && h.removeClass('jv-open');
                    return;
                }
                
                if (!(D & (L | R))) {
                                                                                
                    height = b1.height();
                    width = b1.width();
                    var value = height + scroll + 350;
                    b1.css({top: -(scroll), height: value, width: width});
                    h.height(value);
                    w.scrollTop(scroll);
                    h.addClass('jv-open');
                    f.each(function()
                    {
                        var fixed = $(this)
                           ,top = fixed.css('top');
                           
                        top = 'auto' === top ? 0 : top;                        
                        fixed.css({top: (parseInt(top) + scroll), position: 'absolute'});
                    });
                                        
                    if (touch) {
                        p.height(height + 350);
                    } else {
                        p.css({top: webkit ? 0 : scroll});                        
                    }
                    
                    b2.scrollTop(0);
                    
                }
                
                var value = b2.find('div:not(:empty):first').outerWidth()
                   ,props = {};
                   
                if (null === value)
                    value = b2.find('div:first').outerWidth();
                
                --value;
                
                switch (dir) {

                    // left
                    case L:
                        !(D & R) && b1.css('right', 'auto');
                        props = {left: D & L ? 0 : value, right: D & R ? 0 : 'auto'};
                        D = D & L ? 0 : L;
                        (D & L) && h.removeClass('jv-open-right').addClass('jv-open-left');                                
                        break;

                    // right
                    case R:                
                        !(D & L) && b1.css('left', 'auto');                
                        props = {right: D & R ? 0 : value, left: D & L ? -(value) : 'auto'};
                        D = D & R ? 0 : R;
                        (D & R) && h.removeClass('jv-open-left').addClass('jv-open-right');                                    
                        break;

                    default:
                        break;            
                };                
                                          
                if (true === opts.transforms) {

                    animating = true;
                    
                    var value = Math.abs('auto' !== props.left ? props.left : props.right)
                       ,css = {};

                    // opening
                    if (value >= 0) {
                        
                        if ('auto' !== props.right &&
                            'auto' !== props.left) {
                            value = props.right;
                        }
                        
                        css = transform((props.right !== 'auto' ? '-' : '') + value);

                    // closing
                    } else {                              
                        css = transform(value);
                    }
                    
                    b1.on('transitionend webkitTransitionEnd oTransitionEnd', function(ee)
                    {
                        animating = false;
                        b1.off(ee);
                        complete(e);
                    }).css(css);
                    
                } else {
                    b1.delay(5).stop(true, false).animate(
                        props,
                        opts.slide.duration,
                        opts.slide.easing,
                        function()
                        {
                            complete(e);
                        }
                    );                    
                }
            };
            
            self.trigger(e);
        };
        
        this.on = function()
        {            
            return self.on.apply(self, arguments).get(0);
        };

        this.on('close', function(e)
        {
            if (!e.isDefaultPrevented())
                t.removeClass('jv-active');                
        });
        
        this.off = function()
        {            
            return self.off.apply(self, arguments).get(0);
        };
        
        this.enable = function()
        {
            if (enabled && loaded)
                return this;
                        
            return self.trigger('enable').get(0);            
        };
        
        this.onenable = function(e)
        {
            if ((enabled && loaded) || e.isDefaultPrevented())
                return;

            scroll = w.scrollTop();

            if (!b1.length) {
                b1 = $(b1.selector);
                b2.insertAfter(b1);
            }

            if (!t.length)
                t = $(t.selector);
                        
            if (!p.length) {
                
                p = $(p.selector);
                
                if (!p.length)
                    p = $('<div data-jv-panel="left" /><div data-jv-panel="right" />');
            }
                                                
            if (1 === p.length) {
                if (0 === p.filter('[data-jv-panel="left"]').length) {
                    p = p.add('<div data-jv-panel="left" />');
                } else {
                    p = p.add('<div data-jv-panel="right" />');                    
                }
            }
            
            p.addClass('jv-panel');
                        
            if (p.parent().length) {
                
                p = p.filter(':first, :last').sort(function(a, b)
                {
                    a = a.getAttribute('data-jv-panel') || a.dataset.vettePanel;
                    b = b.getAttribute('data-jv-panel') || b.dataset.vettePanel;
                    return (a > b) ? 1 : (a < b) ? -1 : 0;
                });
                                
                p.each(function(i, o)
                {                                        
                    var o = $(o)
                       ,a = o.data('jv-holder');

                    if ('undefined' === typeof a) {
                        a = $('<a />').addClass('jv-holder');
                        a.attr('id', 'jv-panel-' + o.data('jv-panel'));   
                        o.attr('data-jv-holder', a.attr('id'));
                    }
                    
                    if (o.parent().get(0) !== b2.get(0))
                        o.replaceWith(a);
                    else
                        a.replaceWith(o);
                });
            }
                
            if (b2.is(':empty')) {
                b2.append(p.first());
                b2.append(p.last());
            }
            
            c = $(c.selector).sort(function(a, b)
            {
                a = parseInt(a.getAttribute('data-jv-order') || a.dataset.vetteOrder || 0);
                b = parseInt(b.getAttribute('data-jv-order') || b.dataset.vetteOrder || 0);
                return (a > b) ? 1 : (a < b) ? -1 : 0;
            });
            
            c = c.each(function(i, o)
            {
                var o = $(o)
                   ,a = o.data('jv-holder');
                
                if ('undefined' === typeof a) {
                    a = $('<a />').addClass('jv-holder');
                    a.attr('id', 'jv-content-' + i);
                    o.attr('data-jv-holder', a.attr('id'));
                }

                if (o.is(':hidden'))
                    o.addClass('jv-hidden');
                
                o.replaceWith(a);
                
                var dir = $.trim(o.data('jv-content'));
                
                switch (true) {

                    case /^r/i.test(dir):
                        p.last().append(o);
                        break;
                        
                    case /^l/i.test(dir):
                    default:
                        p.first().append(o);
                        break;
                }
                
            }).addClass('jv-content').show(0);
            
            i = $(i.selector);
            f = $(f.selector);
            
            i.off(touch ? 'touchmove' : 'mousemove', lock)
             .on(touch ? 'touchmove' : 'mousemove', lock)
             .off(touch ? 'touchend' : 'mouseout', unlock)
             .on(touch ? 'touchend' : 'mouseout', unlock);
                        
            t.off(touch ? 'touchend' : 'click', click)
             .on(touch ? 'touchend' : 'click', click);
            
            t.each(function(i,o)
            {
                o['on' + (touch ? 'touchend' : 'click')] = trigger;
            });
            
            if (false !== opts.swipe && $.fn.swipe) {
                
                d.swipe({

                    swipe: function(event, direction, distance, duration, fingerCount, fingerData) {

                        if (!enabled || locked || 'undefined' !== typeof timer) {
                            return true;
                        }

                        var e = new jVette.SwipeEvent();
                        e.target = this.get(0);
                        e.relatedTarget = this.get(0);
                        e.direction = direction;
                        e.distance = distance;
                        e.duration = duration;
                        e.fingerCount = fingerCount;
                        e.fingerData = fingerData;

                        switch (direction) {
                            case 'left':
                                !(D & R) && split(D & L ? L : R, e);
                                break;

                            case 'right':
                                !(D & L) && split(D & R ? R : L, e);
                                break;

                            default:
                                break;
                        };
                    }
                    ,threshold: opts.swipe.threshold
                    ,allowPageScroll: 'vertical'
                });
            }
            
            enabled = true;
            loaded = true;
     
            self.trigger('enabled');           
        };

        this.disable = function()
        {
            if (!enabled)
                return this;
            
            return self.trigger('disable').get(0);                        
        };
        
        var disable = function()
        {
            c.each(function(i, o)
            {                
                var o = $(o)
                   ,id = o.data('jv-holder')
                   ,a = b1.find('a#' + id);
                
                if (a.length)
                    a.replaceWith(o);
                
                if (o.hasClass('jv-hidden')) {
                    o.hide(0).removeClass('jv-hidden');
                } else {
                    o.css('display', '');
                }
                
                o.removeData('jv-holder')
                 .removeAttr('data-jv-holder');
         
            }).removeClass('jv-content');

            p.each(function(i, o)
            {
                var o = $(o)
                   ,id = o.data('jv-holder')
                   ,a = b1.find('a#' + id);

                if (a.length)
                    a.replaceWith(o);
                
                o.removeData('jv-holder')
                 .removeAttr('data-jv-holder');
         
            }).css('display', '');
            
            i.off('mousemove touchmove', lock)
             .off('mouseleave mouseup touchend', unlock);

            t.off('touchend click', click);
            t.each(function(i, o)
            {
                delete o['on' + (touch ? 'touchend' : 'click')];
            });
            
            enabled = false;

            self.trigger('disabled');                        
        };
    
        this.ondisable = function(e)
        {
            if (!enabled || e.isDefaultPrevented())
                return;

            t.off('touchstart mousedown', trigger);

            if (false !== opts.swipe && $.fn.swipe)
                $('.jv-panel,.jv-ignore').off('mousemove touchmove',  function(e)
                {
                    e.preventDefault();
                });

            if (D & (L | R)) {
                this.on('closed', function(e)
                {
                    this.off(e);                    
                    disable();
                }).close();
            } else {
                disable();                
            }
        };

        this.open = function(v)
        {
            if (!enabled)
                return this;
            
            switch (true) {
                
                case /^r/i.test(v) && !(D & R):
                    split(R, new jVette.TriggerEvent('open'));
                    break;

                case /^l/i.test(v) && !(D & L):
                    split(L, new jVette.TriggerEvent('open'));
                    break;

                default:
                    break;
            };
            
            return this;
        };
        
        this.close = function()
        {            
            
            if (!enabled || !(D & (L | R)))
                return this;
                
            split(D & L ? L : R, new jVette.TriggerEvent('close'));
            
            return this;
        };
       
        opts.slide.easing = $.easing && $.easing[opts.slide.easing] ? opts.slide.easing : 'swing';
                
        if (null === easing(opts.slide.easing))
            opts.slide.easing = 'swing';
        
        $.each([
            'enable'
           ,'enabled'
           ,'disable'
           ,'disabled'
           ,'open'
           ,'opened'
           ,'close'
           ,'closed'], function(i,e) {
            'function' === typeof opts['on'+e] && self.on(e, opts['on'+e]);
        });

        if (enabled)
            this.enable();        
    };
    
    jVette.TriggerEvent = function(t, e)
    {
        $.Event.apply(this, [t || null]);
        this.panel = e && e.panel || undefined;
        this.direction = e && e.direction || null;
    };

    jVette.TriggerEvent.prototype = new $.Event();
    jVette.TriggerEvent.prototype.constructor = jVette.TriggerEvent;

    jVette.SwipeEvent = function(t, e)
    {
        jVette.TriggerEvent.apply(this, [t, e]);
        this.distance = e && e.distance || 0;
        this.duration = e && e.duration || 0;
        this.fingerCount = e && e.fingerCount || 0;
        this.fingerData = e && e.fingerData || {};
    };

    jVette.SwipeEvent.prototype = new jVette.TriggerEvent();
    jVette.SwipeEvent.prototype.constructor = jVette.SwipeEvent;        
        
    return jVette;
    
})(window.jQuery, window, document, undefined); 