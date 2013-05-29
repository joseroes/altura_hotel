/* Mobile Joomla! 1.2.5.1 | mobilejoomla.com/license.html */
var jQT = new $.jQTouch({
	addGlossToIcon: true,
	statusBar: 'black'
});

$(function()
{
	if(isFrontPage)
	{
		$("a.back").hide();
		$("a.home").hide();
	}

	$("ul#mainlevel li").each(function()
	{
		$(this).addClass("forward");
	});
});