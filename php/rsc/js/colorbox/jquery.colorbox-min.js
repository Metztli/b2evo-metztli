// ColorBox v1.3.17.2 - a full featured, light-weight, customizable lightbox based on jQuery 1.3+
// Copyright (c) 2011 Jack Moore - jack@colorpowered.com
// Licensed under the MIT license: http://www.opensource.org/licenses/mit-license.php
(function(e,t,n){function Q(n,r,i){i=t.createElement("div");if(n){i.id=s+n}i.style.cssText=r||"";return e(i)}function G(e,t){return Math.round((/%/.test(e)?(t==="x"?N.width():N.height())/100:1)*parseInt(e,10))}function Y(e){return B.photo||/\.(gif|png|jpg|jpeg|bmp)(?:\?([^#]*))?(?:#(\.*))?$/i.test(e)}function Z(t){B=e.extend({},e.data(R,i));for(t in B){if(e.isFunction(B[t])&&t.substring(0,2)!=="on"){B[t]=B[t].call(R)}}B.rel=B.rel||R.rel||"nofollow";B.href=B.href||e(R).attr("href");B.title=B.title||R.title;if(typeof B.href==="string"){B.href=e.trim(B.href)}}function et(t,n){if(n){n.call(R)}e.event.trigger(t)}function tt(){var e,t=s+"Slideshow_",n="click."+s,r,i,o;if(B.slideshow&&T[1]){r=function(){M.text(B.slideshowStop).unbind(n).bind(f,function(){if(U<T.length-1||B.loop){e=setTimeout(K.next,B.slideshowSpeed)}}).bind(a,function(){clearTimeout(e)}).one(n+" "+l,i);g.removeClass(t+"off").addClass(t+"on");e=setTimeout(K.next,B.slideshowSpeed)};i=function(){clearTimeout(e);M.text(B.slideshowStart).unbind([f,a,l,n].join(" ")).one(n,r);g.removeClass(t+"on").addClass(t+"off")};if(B.slideshowAuto){r()}else{i()}}else{g.removeClass(t+"off "+t+"on")}}function nt(t){if(!V){R=t;Z();T=e(R);U=0;if(B.rel!=="nofollow"){T=e("."+o).filter(function(){var t=e.data(this,i).rel||this.rel;return t===B.rel});U=T.index(R);if(U===-1){T=T.add(R);U=T.length-1}}if(!W){W=X=true;g.show();if(B.returnFocus){try{R.blur();e(R).one(c,function(){try{this.focus()}catch(e){}})}catch(n){}}m.css({opacity:+B.opacity,cursor:B.overlayClose?"pointer":"auto"}).show();B.w=G(B.initialWidth,"x");B.h=G(B.initialHeight,"y");K.position();if(d){N.bind("resize."+v+" scroll."+v,function(){m.css({width:N.width(),height:N.height(),top:N.scrollTop(),left:N.scrollLeft()})}).trigger("resize."+v)}et(u,B.onOpen);H.add(A).hide();P.html(B.close).show()}K.load(true)}}var r={transition:"elastic",speed:300,width:false,initialWidth:"600",innerWidth:false,minWidth:false,maxWidth:false,height:false,initialHeight:"450",innerHeight:false,minHeight:false,maxHeight:false,scalePhotos:true,scrolling:true,inline:false,html:false,iframe:false,fastIframe:true,photo:false,href:false,title:false,rel:false,opacity:.9,preloading:true,current:"image {current} of {total}",previous:"previous",next:"next",close:"close",openNewWindowText:"open in new window",open:false,returnFocus:true,loop:true,slideshow:false,slideshowAuto:true,slideshowSpeed:2500,slideshowStart:"start slideshow",slideshowStop:"stop slideshow",onOpen:false,onLoad:false,onComplete:false,onCleanup:false,onClosed:false,overlayClose:true,escKey:true,arrowKey:true,top:false,bottom:false,left:false,right:false,fixed:false,data:false,displayVoting:false,votingUrl:""},i="colorbox",s="cbox",o=s+"Element",u=s+"_open",a=s+"_load",f=s+"_complete",l=s+"_cleanup",c=s+"_closed",h=s+"_purge",p=e.browser.msie&&!e.support.opacity,d=p&&e.browser.version<7,v=s+"_IE6",m,g,y,b,w,E,S,x,T,N,C,k,L,A,O,M,_,D,P,H,B,j,F,I,q,R,U,z,W,X,V,$,J,K;K=e.fn[i]=e[i]=function(t,n){var s=this;t=t||{};if(!s[0]){if(s.selector){return s}s=e("<a/>");t.open=true}if(n){t.onComplete=n}s.each(function(){e.data(this,i,e.extend({},e.data(this,i)||r,t));e(this).addClass(o)});if(e.isFunction(t.open)&&t.open.call(s)||t.open){nt(s[0])}return s};K.init=function(){N=e(n);g=Q().attr({id:i,"class":p?s+(d?"IE6":"IE"):""});m=Q("Overlay",d?"position:absolute":"").hide();y=Q("Wrapper");b=Q("Content").append(C=Q("LoadedContent","width:0; height:0; overflow:hidden"),L=Q("LoadingOverlay").add(Q("LoadingGraphic")),$voting=Q("Voting"),A=Q("Title"),O=Q("Current"),_=Q("Next"),D=Q("Previous"),M=Q("Slideshow").bind(u,tt),P=Q("Close"),$open=Q("Open"));y.append(Q().append(Q("TopLeft"),w=Q("TopCenter"),Q("TopRight")),Q(false,"clear:left").append(E=Q("MiddleLeft"),b,S=Q("MiddleRight")),Q(false,"clear:left").append(Q("BottomLeft"),x=Q("BottomCenter"),Q("BottomRight"))).children().children().css({"float":"left"});k=Q(false,"position:absolute; width:9999px; visibility:hidden; display:none");e("body").prepend(m,g.append(y,k));voting_positions_done=false;previous_title="";b.children().hover(function(){e(this).addClass("hover")},function(){e(this).removeClass("hover")}).addClass("hover");j=w.height()+x.height()+b.outerHeight(true)-b.height();F=E.width()+S.width()+b.outerWidth(true)-b.width();I=C.outerHeight(true);q=C.outerWidth(true);g.css({"padding-bottom":j,"padding-right":F}).hide();_.click(function(){K.next()});D.click(function(){K.prev()});P.click(function(){K.close()});$open.click(function(){K.close()});H=_.add(D).add(O).add(M);b.children().removeClass("hover");m.click(function(){if(B.overlayClose){K.close()}});e(t).bind("keydown."+s,function(e){var t=e.keyCode;if(W&&B.escKey&&t===27){e.preventDefault();K.close()}if(W&&B.arrowKey&&T[1]){if(t===37){e.preventDefault();D.click()}else if(t===39){e.preventDefault();_.click()}}})};K.remove=function(){g.add(m).remove();e("."+o).removeData(i).removeClass(o)};K.position=function(e,n){function o(e){w[0].style.width=x[0].style.width=b[0].style.width=e.style.width;L[0].style.height=L[1].style.height=b[0].style.height=E[0].style.height=S[0].style.height=e.style.height}var r=0,i=0;N.unbind("resize."+s);g.hide();if(B.fixed&&!d){g.css({position:"fixed"})}else{r=N.scrollTop();i=N.scrollLeft();g.css({position:"absolute"})}if(B.right!==false){i+=Math.max(N.width()-B.w-q-F-G(B.right,"x"),0)}else if(B.left!==false){i+=G(B.left,"x")}else{i+=Math.round(Math.max(N.width()-B.w-q-F,0)/2)}if(B.bottom!==false){r+=Math.max(t.documentElement.clientHeight-B.h-I-j-G(B.bottom,"y"),0)}else if(B.top!==false){r+=G(B.top,"y")}else{r+=Math.round(Math.max(t.documentElement.clientHeight-B.h-I-j,0)/2)}g.show();e=g.width()===B.w+q&&g.height()===B.h+I?0:e||0;y[0].style.width=y[0].style.height="9999px";g.dequeue().animate({width:B.w+q,height:B.h+I,top:r,left:i},{duration:e,complete:function(){o(this);X=false;y[0].style.width=B.w+q+F+"px";y[0].style.height=B.h+I+j+"px";if(n){n()}setTimeout(function(){N.bind("resize."+s,K.position)},1)},step:function(){o(this)}});if(C.width()<380){O.hide();M.hide()}};K.resize=function(e){if(W){e=e||{};if(e.width){B.w=G(e.width,"x")-q-F}if(e.innerWidth){B.w=G(e.innerWidth,"x")}C.css({width:B.w});if(e.height){B.h=G(e.height,"y")-I-j}if(e.innerHeight){B.h=G(e.innerHeight,"y")}if(!e.innerHeight&&!e.height){var t=C.wrapInner("<div style='overflow:auto'></div>").children();B.h=t.height();t.replaceWith(t.children())}C.css({height:B.h});K.position(B.transition==="none"?0:B.speed)}};K.prep=function(t){function o(){B.w=B.w||C.width();B.w=B.mw&&B.mw<B.w?B.mw:B.w;B.w=B.minWidth&&B.minWidth>B.w?B.minWidth:B.w;return B.w}function u(){B.h=B.h||C.height();B.h=B.mh&&B.mh<B.h?B.mh:B.h;B.h=B.minHeight&&B.minHeight>B.h?B.minHeight:B.h;return B.h}if(!W){return}var n,r=B.transition==="none"?0:B.speed;C.remove();C=Q("LoadedContent").append(t);C.hide().appendTo(k.show()).css({width:o(),overflow:B.scrolling?"auto":"hidden"}).css({height:u()}).prependTo(b);k.hide();e(z).css({"float":"none"});if(d){e("select").not(g.find("select")).filter(function(){return this.style.visibility!=="hidden"}).css({visibility:"hidden"}).one(l,function(){this.style.visibility="inherit"})}n=function(){function d(){if(p){g[0].style.removeAttribute("filter")}}var t,n,o,u,a=T.length,l,c;if(!W){return}c=function(){clearTimeout(J);L.hide();et(f,B.onComplete)};if(p){if(z){C.fadeIn(100)}}A.html(B.title).add(C).show();if(a>1){if(typeof B.current==="string"&&C.width()>380){O.html(B.current.replace("{current}",U+1).replace("{total}",a)).show()}_[B.loop||U<a-1?"show":"hide"]().html(B.next);D[B.loop||U?"show":"hide"]().html(B.previous);t=U?T[U-1]:T[a-1];o=U<a-1?T[U+1]:T[0];if(B.slideshow&&C.width()>380){M.show()}if(B.preloading){u=e.data(o,i).href||o.href;n=e.data(t,i).href||t.href;u=e.isFunction(u)?u.call(o):u;n=e.isFunction(n)?n.call(t):n;if(Y(u)){e("<img/>")[0].src=u}if(Y(n)){e("<img/>")[0].src=n}}}else{H.hide()}if(B.iframe){l=e("<iframe/>").addClass(s+"Iframe")[0];if(B.fastIframe){c()}else{e(l).one("load",c)}l.name=s+ +(new Date);l.src=B.href;if(!B.scrolling){l.scrolling="no"}if(p){l.frameBorder=0;l.allowTransparency="true"}e(l).appendTo(C).one(h,function(){l.src="//about:blank"})}else{c()}if(B.transition==="fade"){g.fadeTo(r,1,d)}else{d()}};if(B.transition==="fade"){g.fadeTo(r,0,function(){K.position(0,n)})}else{K.position(r,n)}};K.load=function(t){var n,r,i=K.prep;X=true;z=false;R=T[U];if(!t){Z()}et(h);et(a,B.onLoad);if(previous_title!=B.title){previous_title=B.title;var o=0;if(B.title!==""){var u=0;if(B.displayVoting&&B.votingUrl!=""){u=$voting.outerHeight()}A.css("margin-bottom",u+P.outerHeight()-3);o=15}else{o=-15}C.css("margin-bottom",parseInt(C.css("margin-bottom"))+o);I+=o}if(B.displayVoting&&B.votingUrl!=""&&R.id!=""){if(!voting_positions_done){C.css("margin-bottom",parseInt(C.css("margin-bottom"))+$voting.outerHeight());I=C.outerHeight(true);voting_positions_done=true}init_voting_bar($voting,B.votingUrl,R.id,true)}B.h=B.height?G(B.height,"y")-I-j:B.innerHeight&&G(B.innerHeight,"y");B.w=B.width?G(B.width,"x")-q-F:B.innerWidth&&G(B.innerWidth,"x");B.mw=B.w;B.mh=B.h;if(B.maxWidth){B.mw=G(B.maxWidth,"x")-q-F;B.mw=B.w&&B.w<B.mw?B.w:B.mw}if(B.maxHeight){B.mh=G(B.maxHeight,"y")-I-j;B.mh=B.h&&B.h<B.mh?B.h:B.mh}n=B.href;J=setTimeout(function(){L.show()},100);if(B.inline){Q().hide().insertBefore(e(n)[0]).one(h,function(){e(this).replaceWith(C.children())});i(e(n))}else if(B.iframe){i(" ")}else if(B.html){i(B.html)}else if(Y(n)){e(z=new Image).addClass(s+"Photo").error(function(){B.title=false;i(Q("Error").text("This image could not be loaded"))}).load(function(){var e;z.onload=null;if(B.scalePhotos){r=function(){z.height-=z.height*e;z.width-=z.width*e};if(B.mw&&z.width>B.mw){e=(z.width-B.mw)/z.width;r()}if(B.mh&&z.height>B.mh){e=(z.height-B.mh)/z.height;r()}}if(B.h){z.style.marginTop=Math.max(B.h-z.height,0)/2+"px"}if(T[1]&&(U<T.length-1||B.loop)){z.style.cursor="pointer";z.onclick=function(){K.next()}}if(p){z.style.msInterpolationMode="bicubic"}setTimeout(function(){i(z)},1)});setTimeout(function(){z.src=n},1);$open.html('<a href="'+n+'" target="_blank">'+B.openNewWindowText+"</a>").show()}else if(n){k.load(n,B.data,function(t,n,r){i(n==="error"?Q("Error").text("Request unsuccessful: "+r.statusText):e(this).contents())})}};K.next=function(){if(!X&&T[1]&&(U<T.length-1||B.loop)){U=U<T.length-1?U+1:0;K.load()}};K.prev=function(){if(!X&&T[1]&&(U||B.loop)){U=U?U-1:T.length-1;K.load()}};K.close=function(){if(W&&!V){V=true;W=false;et(l,B.onCleanup);N.unbind("."+s+" ."+v);m.fadeTo(200,0);g.stop().fadeTo(300,0,function(){g.add(m).css({opacity:1,cursor:"auto"}).hide();et(h);C.remove();setTimeout(function(){V=false;et(c,B.onClosed)},1)})}};K.element=function(){return e(R)};K.settings=r;$=function(e){if(!(e.button!==0&&typeof e.button!=="undefined"||e.ctrlKey||e.shiftKey||e.altKey)){e.preventDefault();nt(this)}};if(e.fn.delegate){e(t).delegate("."+o,"click",$)}else{e(t).on("click","."+o,$)}e(K.init)})(jQuery,document,this)