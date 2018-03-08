(function(){
        /* HTML 标签字体处理 */
        var HTMLFontSize = {
            // 延时触发ID
            timeroutID: null,
            
            // 更改字体大小
            change: function() {

                // 文档元素,app-container元素
                var docElement = document.documentElement,//750/15
                    appWidth = docElement.clientWidth > 750 ? 750 : docElement.clientWidth;
                // 设置字体大小
                docElement.style.fontSize = appWidth / 15 + "px";
            },

            // 绑定更改窗口大小事件
            bindEvent: function(){
                var that = this;

                // 绑定调整窗口大小事件
                window.onresize = function(){

                    // 清除ID
                    clearTimeout(that.timeroutID);
                    // 延时
                    that.timeroutID = setTimeout(that.change, 100);
                };
            },
            
            // 初始化
            init: function(){
                // 绑定更改窗口大小事件
                this.bindEvent();
                // 首次更改字体大小
                this.change();
            }
        };

        // 初始化
        HTMLFontSize.init();
}());



