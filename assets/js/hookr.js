;(function($, w, d, undefined)
{
    $(function()
    {
        var xhr = null,
            script = $('#script-hooks');
        
        var menu = $('#wp-admin-bar-hookr');
        
        menu.find('input[id^=hookr-enabled]').on('change', function()
        {            
            var enabled = parseInt($(this).val())
               ,input = menu.find('input').attr('disabled', 'disabled');
           
            xhr && xhr.abort();
            xhr = $.ajax({
                type: 'post',
                url: hookr.ajax_url,
                data: { action: 'ajax', type: 'enable', enabled: 1 === enabled, key: key, id: $(this).data('hookr') },
                success: function(data)
                {
                    input.removeAttr('disabled');                    
                    location.reload();
                }
            });                    
        });        
               
        if (0 === script.length)
            return;
                
        var hooks = JSON.parse(script.text())        
           ,offcanvas = $('#hookr-offcanvas').addClass('hookr-filter')
           ,ids = [] 
           ,cls = []
           ,html = $('html')
           ,body = $('body');
                                      
        var index = null
           ,url = html.data('hookr-url')
           ,key = html.data('hookr-key')
           ,nodes = $('.hookr-action, .hookr-marker').each(function(i)
        {
            var node = $(this)
               ,list = $('<ol>')
               ,skip = false
               ,keys = !node.hasClass('hookr-action')
                     ? node.parent().data('hookr')
                     : node.data('hookr');
                         
            if ('undefined' === typeof keys)
                return;
            
            var hook = null;
            
            $.each(keys, function()
            {
                hook = hooks[this.valueOf()];
                list.append('<li id="hookref-' + hook.index + '"><a href="#' + hook.tag + '" data-hookr="' + hook.index + '"><pre data-hookr><strong>' + hook.tag + '</strong> | ' + hook.value + '</pre></a></li>');
            });
                        
            if (node.hasClass('hookr-marker'))
                node.attr('title', 'Total ' + keys.length);
               
            var img = $('<img src="' + url + 'assets/images/hookr-white.svg" />')
                ,search = $('<input type="search" data-id="'+ i +'"class="hookr-qtip-search" placeholder="filter the things&hellip;" />')
                ,header = $.merge(img, $('<button>'))
                ,items = list.find('>li')
                ,value = ''
                ,timer;

            node.removeData('hookr-list');
              
            if (node.hasClass('hookr-marker') && items.length >= 5) {
                
                header = $.merge(header, search);
                
                search.on('keyup change', function(e)
                {
                    // TODO(cs) Change to _.debounce;
                    setTimeout(timer);
                    timer = setTimeout(function()
                    {                                    
                        value = $.trim(search.val());

                        if (value.length <= 2) {
                            items.css('display', '');                    
                            return;
                        }

                        items.css('display', 'none');             

                        $.each(index.search(value), function()
                        {
                            items.filter('#hookref-' + this.ref).css('display', '');
                        });

                    }, 100);                
                });
            }
            
            node.qtip({
                content: {
                    title: ' '                    
                }
               ,hide: {
                    delay: 1500
                   ,fixed: true
                   ,event: 'mouseleave'
                }
               ,show: {
                    delay: 100
                   ,effect: false
                   ,solo: true
               }
               ,position: {
                    my: 'center right'
                   ,at: 'center left'
                   ,viewport: $(window)
                   ,adjust: {
                       mouse: true
                      ,resize: true
                      ,method: 'flip flip'
                  }
                }
                ,style: {
                    classes: 'qtip-parent qtip-light qtip-shadow'
                   ,widget: false
                   ,tip: {
                        corner: true
                        ,mimic: false
                        ,width: 10
                        ,height: 10
                        ,border: 0
                        ,offset: 0
                    }                
                }
               ,events: {
                    hide: function(e, api)
                    {                        
                        var container = api.elements.titlebar.parent()
                           ,content = api.elements.content;
                           
                        container.removeClass('qtip-detail qtip-ajax');    
                        content.find('>:not(ol)').remove();
                    },
                    render: function(e, api)                   
                    {
                        var container = api.elements.titlebar.parent() 
                           ,titlebar = api.elements.titlebar
                           ,title = api.elements.title
                           ,content = api.elements.content
                           ,target = api.target;
                           
                        title.append(header);
                        title.find('button').on('click', function(e)
                        {
                            titlebar.parent().removeClass('qtip-detail');
                        });

                        search.on('focus', function()
                        {
                            target.css('width', api.target.outerWidth());

                        }).on('blur', function()
                        {
                            target.css('width', '');
                        });
                        
                        content.empty().append(list);
                        
                        list.find('a').on('click', function(e)
                        {
                            container.addClass('qtip-detail qtip-ajax');
                            content.find('>dl').remove();
                            
                            xhr && xhr.abort();
                            xhr = $.ajax({
                                type: 'post',
                                url: hookr.ajax_url,
                                data: { action: 'ajax', type: 'detail', key: key, id: $(this).data('hookr') },
                                success: function(data)
                                {
                                    container.removeClass('qtip-ajax');
                                    content.append(data);
                                }
                            });
                        });
                    }                  
                }
            });            
        });
        
        $('.qtip').scrollLock('on', '.qtip-content');
        
        $(w).load(function()
        {
            var search = null, 
                timer;
            
            var actions = $('.hookr-action:visible')
               ,filters = $('.hookr-filter:visible');
               
            var reset = function()
            {
                actions.removeClass('hookr-active').css('display', '');
                filters.removeClass('hookr-active').find('> .hookr-marker').css('display', '');                
            };
                        
            index = lunr(function () {
                this.field('tag', { boost: 10 });
                this.field('value');
                this.ref('index');
            }); 
              
            $('#wp-admin-bar-hookr').on('keyup change', 'input[type=search]', function(e)
            {
                if (null == search)
                    search = $(this); 
                
                clearTimeout(timer);
                timer = setTimeout(function()
                {
                    reset();
                    
                    // Tom-foolery.
                    html.addClass('hookr-search');                    
                    actions.css('display', 'none');
                    filters.find('> .hookr-marker').css('display', 'none');                    
                    html.removeClass('hookr-search');
                    
                    var value = $.trim(search.val());
                    
                    if (value.length < 3) {
                        reset();
                        return;
                    }
                    
                    var results = index.search(value);
                    
                    if (0 === results.length) {
                        reset();
                        return;
                    }
                                        
                    $.each(results, function(i)
                    {
                        //var pct = this.score.toFixed(5) + '%';
                        
                        var action = actions
                            .filter('#hookr-' + this.ref)
                            .addClass('hookr-active')
                            .css('display', '');
                    
                        //action.text(pct);
                        
                        var filter = filters
                            .filter('.hookr-' + this.ref)
                            .addClass('hookr-active')
                            .find('> .hookr-marker')                            
                            .css('display', '');
                    
                        //filter.text(pct);
                    });
                    
                }, 100);
                
            });

            $.each(hooks, function(i, hook)
            {
                hook.tag += ' ' + hook.tag.split('_').join(' ');
                index.add(hook);  
            });
            
            var target = $('#screen-meta div.hidden');
            $('#screen-meta-links button').on('click', function(e)
            {
                var id = $(this).parent().attr('id').replace('-link', '');                
                target.filter('#' + id).removeClass('hidden');
            });
        });
    });    
    
})(window.jQuery, window, document, undefined);
