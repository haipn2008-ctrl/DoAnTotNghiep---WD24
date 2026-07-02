(function(){
    // Patch ApexCharts constructor: if element is null, return a noop object
    if (typeof window !== 'undefined' && window.ApexCharts) {
        (function(){
            var _OriginalApex = window.ApexCharts;
            window.ApexCharts = function(el, opts){
                if(!el) {
                    return { render:function(){}, destroy:function(){}, updateOptions:function(){}, updateSeries:function(){} };
                }
                return new _OriginalApex(el, opts);
            };
            window.ApexCharts.prototype = _OriginalApex.prototype;
        })();
    }

    // Override getChartColorsArray to be resilient to missing data-colors
    window.getChartColorsArray = function(selector){
        try{
            var el = document.querySelector(selector);
            if(!el) return [];
            var s = el.getAttribute('data-colors');
            if(!s) return [];
            var arr;
            try{ arr = JSON.parse(s); } catch(e){ return []; }
            return arr.map(function(item){
                item = (''+item).replace(' ', '');
                if(item.indexOf('--') === -1) return item;
                var v = getComputedStyle(document.documentElement).getPropertyValue(item);
                return v || undefined;
            });
        }catch(e){ return []; }
    };
})();
