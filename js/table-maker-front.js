jQuery(document).ready(function($) {		
	$('.wpsm-comptable').columnHover({eachCell:true, hoverClass:'betterhover', includeSpans:false}); 	// cross hover column and row 

	$('.wpsm-comptable').each(function(){
		var badge_col = $(this).find('.badge_div_col').closest('td');
		var selected_cols = badge_col.parent().children().index(badge_col) + 1;
		$(this).find('tr > td:nth-child('+selected_cols+')').addClass('editor_selected_col');
		$(this).find('tr > th:nth-child('+selected_cols+')').addClass('editor_selected_col');
		
		var selected_rows = $(this).find('tr').index($('.badge_div_row').closest('tr'));
		if (selected_rows != -1) {
			$(this).find('tr:eq('+selected_rows+')').addClass('editor_selected_row');
			//$(this).find('tr:eq('+selected_rows+')').prev().addClass('editor_prev_selected_row');
		}

	}); 

	$('.wpsm-comptable-responsive').each(function(){
		$(this).stacktable();
	}); 

	$('.wpsmt-column-stack').each(function(){
		$(this).stackcolumns({myClass:'wpsmt-column-stack-mobile'});
	}); 

	$('.wpsmt-column-stack-mobile').each(function(){
		$(this).addClass('wpsm-comptable');
		var tdfix = $(this).find('td.st-key[colspan]');
		tdfix.attr('colspan', '2');
		tdfix.addClass('wpsm-spec-heading');
		tdfix.next('td').remove();
	});	

});
/*
 * jQuery columnHover plugin
 * Version: 0.1.1
 *
 * Copyright (c) 2007 Roman Weich
 * http://p.sohei.org
 *
 * Dual licensed under the MIT and GPL licenses 
 * (This means that you can choose the license that best suits your project, and use it accordingly):
 *   http://www.opensource.org/licenses/mit-license.php
 *   http://www.gnu.org/licenses/gpl.html
 */
!function(e){var o=function(e){for(var o=e.rows,n=o.length,r=[],l=0;n>l;l++)for(var a=o[l].cells,t=a.length,s=0;t>s;s++){var i=a[s],c=i.rowSpan||1,h=i.colSpan||1,d=-1;r[l]||(r[l]=[]);for(var f=r[l];f[++d];);i.realIndex=d;for(var u=l;l+c>u;u++){r[u]||(r[u]=[]);for(var v=r[u],C=d;d+h>C;C++)v[C]=1}}};e.fn.columnHover=function(n){var r=e.extend({hoverClass:"hover",eachCell:!1,includeSpans:!0,ignoreCols:[]},n),l=function(o,n,l){var a=n[o.realIndex],t=0;if(-1==e(r.ignoreCols).index(o.realIndex+1)){for(;++t<o.colSpan;)a=a.concat(n[o.realIndex+t]);l?e(a).addClass(r.hoverClass):e(a).removeClass(r.hoverClass)}},a=function(e,o){e.bind("mouseover",function(){l(this,o,!0)}).bind("mouseout",function(){l(this,o,!1)})};return this.each(function(){var n,l,t,s,i,c,h,d,f=[],u=this;if(u.tBodies&&u.tBodies.length&&u.tHead&&r.hoverClass.length){for(o(u),s=0;s<u.tBodies.length;s++)for(n=u.tBodies[s],i=0;i<n.rows.length;i++)for(l=n.rows[i],c=0;c<l.cells.length;c++)if(t=l.cells[c],r.includeSpans||!(t.colSpan>1)){for(d=r.includeSpans?t.colSpan:1;--d>=0;)h=t.realIndex+d,f[h]||(f[h]=[]),f[h].push(t);r.eachCell&&a(e(t),f)}a(e("td, th",u.tHead),f),r.eachCell&&u.tFoot&&a(e("td, th",u.tFoot),f)}})}}(jQuery);