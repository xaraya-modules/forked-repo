/* jQuery Accessibility Plugin (ability) - A jQuery plugin to provide accessibility functions
 * Author: Tane Piper (digitalspaghetti@gmail.com) 
 * Website: http://code.google.com/p/ability/
 * Licensed under the MIT License: http://www.opensource.org/licenses/mit-license.php
 * Version 1.0
 */
(function($){function switchStyleSheet(a,b){$('link[@rel*=stylesheet]').each(function(){this.disabled=true;if(jQuery(this).attr('href')==b.styledir+a)this.disabled=false;if(b.savecookie==true){jQuery.cookie('style',a,365)}})}function switchTextSize(a,b){jQuery('body').removeClass().addClass(a);if(b.savecookie==true){jQuery.cookie('textsize',a,365)}}function reset(a){if(a.textsizer==true){jQuery('body').removeClass();jQuery.cookie('textsize',null,{expires:-1})}if(a.switcher==true){switchStyleSheet(a.defaultcss,a);jQuery.cookie('style',null,{expires:-1})}}$.fn.extend({ability:function(h){var j="0.1";h=jQuery.extend({textsizer:true,textsizeclasses:['m','l','xl','xxl'],switcher:true,switcherstyles:['default.css','high-contrast.css'],styledir:"/css/",savecookie:true,defaultcss:'default.css'},h);return this.each(function(){controlbox=this;var a='<div class="ability">';var b='<br style="clear:both;" />';var c=jQuery.cookie('style');var d=jQuery.cookie('textsize');if(h.textsizer==true){if(d){jQuery('body').removeClass().addClass(d)}var e='<ul class="fontsize">';for(var i=0,len=h.textsizeclasses.length;i<len;i++){e+='<li><a href="#" rel="'+h.textsizeclasses[i]+'">'+h.textsizeclasses[i].toUpperCase()+'</a></li>'}e+='</ul>';a+=e+b}if(h.switcher==true){if(c){switchStyleSheet(c,h)}var f='<ul class="switcher">';for(var i=0,len=h.switcherstyles.length;i<len;i++){var g=h.switcherstyles[i].split(".");f+='<li><a href="#" rel="'+h.switcherstyles[i]+'">'+g[0].toUpperCase()+'</a></li>'}f+='</ul>';a+=f+b}a+='<a href="#" class="reset">Reset</a></div>';jQuery(controlbox).html(a);jQuery('ul.fontsize li a').bind('click',function(){switchTextSize(jQuery(this).attr('rel'),h);return false});jQuery('ul.switcher li a').bind('click',function(){switchStyleSheet(jQuery(this).attr('rel'),h);return false});jQuery('a.reset').bind('click',function(){reset(h);return false})})}})})(jQuery);