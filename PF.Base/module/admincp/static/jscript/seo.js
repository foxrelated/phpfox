if (typeof $Core.AdminCP == 'undefined') $Core.AdminCP = {};

$Core.AdminCP.Seo =
{
  getURLParameter : function(url, name) {
    return (RegExp(name + '=' + '(.+?)(&|$)').exec(url)||[,null])[1];
  },
  initReplace : function()
	{
		$('.global-settings .help-block a').each(function() {
			var href = $(this).attr('href');
      if(href && href.indexOf('admincp/language/phrase') != -1){
				var phrase =  $Core.AdminCP.Seo.getURLParameter(href, 'q');
				if (phrase) {
          $(this).attr('href', '');
          $(this).attr('target', '');
          $(this).attr('role', 'button');
          $(this).attr('onclick', '$Core.editMeta(\'' + phrase + '\', true);return false;');
				}
      }
		});
	}
};
