try {
    // var elem = document.getElementById("linkid");
    // var elem1 = document.querySelector('.webnavoz_likes_item2');
    // if (typeof elem1.onclick == "function") {
    //     elem1.onclick.apply(elem1);
    // }
    if (typeof webnavoz_likes_cookie_1==='undefined') {var webnavoz_likes_cookie_1 = null;}

    jQuery('body').on('DOMNodeInserted', '.webnavoz_likes', function () {
        function webnavoz_likes_getCookie(name) {
            console.log('used_ws_4');
            var matches = document.cookie.match(new RegExp("(?:^|; )" + name.replace(/([\.$?*|{}\(\)\[\]\\\/\+^])/g, '\\$1') + "=([^;]*)"));
            return matches ? decodeURIComponent(matches[1]) : undefined;
        }
        if (typeof webnavoz_likes_cookie2_1==='undefined') {
            var webnavoz_likes_cookie2_1 = webnavoz_likes_getCookie('webnavoz_likes');
            if (webnavoz_likes_cookie2_1 != undefined) {
                console.log('used_ws_5');
                webnavoz_likes_cookie_1 = jQuery.parseJSON(webnavoz_likes_cookie2_1);
                if (webnavoz_likes_cookie_1 && typeof webnavoz_likes_cookie_1[jQuery(document).find('.percentPointerClass .webnavoz_likes').attr('data-post')] !== 'undefined') {
                    console.log('used_ws_6');
                    jQuery(document).find('.percentPointerClass .webnavoz_likes_item').addClass('progolosoval');
                }
            } else {
                webnavoz_likes_cookie_1 = {};
            }
        }
    });

    jQuery(document).ready(function ($) {
        if (true) {
        // if (false) {
            /* get cookie by name */
            // jQuery(document).on('click','.percentPointerClass .webnavoz_likes_item1', function () {
            //     console.log('used_ws_1');
            //     if (jQuery(this).hasClass('blocked')) {
            //         return false;
            //     }
            //     if (jQuery(this).hasClass('progolosoval')) {
            //         return false;
            //     }
            //     jQuery(document).find('.percentPointerClass .webnavoz_likes_item').addClass('blocked');
            //     var post_id = jQuery(this).parents('.webnavoz_likes').attr('data-post');
            //     jQuery.post('/wp-admin/admin-ajax.php', {action: 'webnavoz_likes_1', post_id: post_id}, function (j) {
            //         console.log('used_ws_1_1');
            //         var bottom_chislo = parseInt(jQuery(document).find('.percentPointerClass .webnavoz_likes_item1 .webnavoz_likes_counter').html());
            //         if (j == 1) {
            //             jQuery(document).find('.percentPointerClass .webnavoz_likes_item').addClass('progolosoval');
            //             /* +1 */
            //             jQuery(document).find('.percentPointerClass .webnavoz_likes_item1 .webnavoz_likes_counter').html((bottom_chislo + 1));
            //             webnavoz_likes_cookie_1[jQuery(document).find('.percentPointerClass .webnavoz_likes').attr('data-post')] = '1';
            //             /* set cookie */
            //             var myDate = new Date();
            //             myDate.setMonth(myDate.getMonth() + 12);
            //             document.cookie = "webnavoz_likes=" + JSON.stringify(webnavoz_likes_cookie_1) + "; path=/; expires=" + myDate;
            //         }
            //     });
            // });
            // jQuery(document).on('click','.percentPointerClass .webnavoz_likes_item2', function () {
            //     console.log('used_ws_2');
            //     if (jQuery(this).hasClass('blocked')) {
            //         return false;
            //     }
            //     if (jQuery(this).hasClass('progolosoval')) {
            //         return false;
            //     }
            //     jQuery(document).find('.percentPointerClass .webnavoz_likes_item').addClass('blocked');
            //     var post_id = jQuery(this).parents('.webnavoz_likes').attr('data-post');
            //     jQuery.post('/wp-admin/admin-ajax.php', {action: 'webnavoz_likes_2', post_id: post_id}, function (j) {
            //         console.log('used_ws_2_1');
            //         var bottom_chislo = parseInt(jQuery(document).find('.percentPointerClass .webnavoz_likes_item2 .webnavoz_likes_counter').html());
            //         if (j == 1) {
            //             jQuery(document).find('.percentPointerClass .webnavoz_likes_item').addClass('progolosoval');
            //             /* +1 */
            //             jQuery(document).find('.percentPointerClass .webnavoz_likes_item2 .webnavoz_likes_counter').html((bottom_chislo + 1));
            //             webnavoz_likes_cookie_1[jQuery(document).find('.percentPointerClass .webnavoz_likes').attr('data-post')] = '1';
            //             /* set cookie */
            //             var myDate = new Date();
            //             myDate.setMonth(myDate.getMonth() + 12);
            //             document.cookie = "webnavoz_likes=" + JSON.stringify(webnavoz_likes_cookie_1) + "; path=/; expires=" + myDate;
            //         }
            //     });
            // });
            // jQuery(document).on('click','.percentPointerClass .webnavoz_likes_item3', function () {
            //     console.log('used_ws_3');
            //     if (jQuery(this).hasClass('blocked')) {
            //         return false;
            //     }
            //     if (jQuery(this).hasClass('progolosoval')) {
            //         return false;
            //     }
            //     jQuery(document).find('.percentPointerClass .webnavoz_likes_item').addClass('blocked');
            //     var post_id = jQuery(this).parents('.webnavoz_likes').attr('data-post');
            //     jQuery.post('/wp-admin/admin-ajax.php', {action: 'webnavoz_likes_3', post_id: post_id}, function (j) {
            //         console.log('used_ws_3_1');
            //         var bottom_chislo = parseInt(jQuery(document).find('.percentPointerClass .webnavoz_likes_item3 .webnavoz_likes_counter').html());
            //         if (j == 1) {
            //             jQuery(document).find('.percentPointerClass .webnavoz_likes_item').addClass('progolosoval');
            //             /* +1 */
            //             jQuery(document).find('.percentPointerClass .webnavoz_likes_item3 .webnavoz_likes_counter').html((bottom_chislo + 1));
            //             webnavoz_likes_cookie_1[jQuery(document).find('.percentPointerClass .webnavoz_likes').attr('data-post')] = '1';
            //             /* set cookie */
            //             var myDate = new Date();
            //             myDate.setMonth(myDate.getMonth() + 12);
            //             document.cookie = "webnavoz_likes=" + JSON.stringify(webnavoz_likes_cookie_1) + "; path=/; expires=" + myDate;
            //         }
            //     });
            // });
            jQuery(document).on('click','.percentPointerClass .webnavoz_likes_item1, .percentPointerClass .webnavoz_likes_item2, .percentPointerClass .webnavoz_likes_item3', function () {
                var lItemIndex = 0;
                let lastClassSymbol = null;
                for (let i = 0; i < this.classList.length; i++) {
                    lastClassSymbol = parseInt(this.classList[i].slice(-1));
                    if ([1,2,3].includes(lastClassSymbol)) {
                        lItemIndex = lastClassSymbol;
                        break;
                    }
                }
                console.log('used_ws_'+lItemIndex);
                if (lItemIndex > 0) {
                    if (jQuery(this).hasClass('blocked')) {
                        return false;
                    }
                    if (jQuery(this).hasClass('progolosoval')) {
                        return false;
                    }
                    jQuery(document).find('.percentPointerClass .webnavoz_likes_item').addClass('blocked');
                    var post_id = jQuery(this).parents('.webnavoz_likes').attr('data-post');
                    jQuery.post('/wp-admin/admin-ajax.php', {action: 'webnavoz_likes_'+lItemIndex, post_id: post_id}, function (j) {
                        console.log('used_ws_'+lItemIndex+'_1');
                        var bottom_chislo = parseInt(jQuery(document).find('.percentPointerClass .webnavoz_likes_item'+lItemIndex+' .webnavoz_likes_counter').html());
                        if (j == 1) {
                            jQuery(document).find('.percentPointerClass .webnavoz_likes_item').addClass('progolosoval');
                            /* +1 */
                            jQuery(document).find('.percentPointerClass .webnavoz_likes_item'+lItemIndex+' .webnavoz_likes_counter').html((bottom_chislo + 1));
                            webnavoz_likes_cookie_1[jQuery(document).find('.percentPointerClass .webnavoz_likes').attr('data-post')] = '1';
                            /* set cookie */
                            var myDate = new Date();
                            myDate.setMonth(myDate.getMonth() + 12);
                            document.cookie = "webnavoz_likes=" + JSON.stringify(webnavoz_likes_cookie_1) + "; path=/; expires=" + myDate;
                        }
                    });
                }
            });
        }
    });

    // function cookiesClean() {
    //     let cookies_all = document.cookie,
    //         cookies_all_array,
    //         cookies_all_final;
    //     if (cookies_all) {
    //         cookies_all_array = cookies_all.split(';');
    //         if (cookies_all_array) {
    //             for (let i = 0; i < cookies_all_array.length; i++) {
    //                 if (cookies_all_array[i].includes('webnavoz_likes')) {
    //                     cookies_all_array.splice(i, 1);
    //                     break;
    //                 }
    //             }
    //             cookies_all_final = cookies_all_array.join(';');
    //             if (cookies_all_final) {
    //                 console.log('wn_coo_cleared');
    //                 document.cookie = cookies_all_final;
    //             }
    //         }
    //     }
    // }
    // cookiesClean();
} catch (e1) {
    console.log(e1.message);
}