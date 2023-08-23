(function ($) {
 	$.fn.ShowConfirmation = function(group, element) {
		$("#component_del-"+group).hide()
		$("#component_del_confirm-"+group).show()
	};

	$.fn.HideEditFields = function() {
		ClearFields("", "", "");
		$("#edit_fields").slideUp();
	};

	$.fn.ShowEditFields = function(destDiv, title, access, subnav) {
		ClearFields(title, access, subnav);
		$("#"+destDiv).append(document.getElementById("edit_fields"));
		$("#edit_fields").slideDown();
	};

	$.fn.AddMessage = function(where, msg) {
		$("#"+where).html("");
		$("#"+where).slideDown();
		$("#"+where).html(msg);

		setTimeout(function() {
			$("#"+where).slideUp();
		}, 5000);
	};


	// Functions called within javascript, not by drupal, can be this style
	function ClearFields(title, access, subnav)
	{
		$("#edit-title-orig").val(title)
		$("#edit-title").val(title)

		if(access == "" || access == "all")
			$("#edit-accesstype-all").prop('checked', true);
		else
			$("#edit-accesstype-individual").prop('checked', true);

		for($I=0; $I<=7; $I++) {
			if (subnav[$I] !== 'undefined') {
				$("#edit-subnav"+($I+1)).val(subnav[$I])
			} else {
				$("#edit-subnav"+($I+1)).val("")
			}
		}
	} 
})(jQuery);


