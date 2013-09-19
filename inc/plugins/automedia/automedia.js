// AutoMedia 2 Plugin for MyBB codebuttons insert
Event.observe(window, 'load', function (amoff) {
	if($('message_new')) {
		var elText      = $('message_new'); 
	} else 	if($('message_old')) {
		var elText      = $('message_old'); 
	} else {
		var elText      = $('message');
	}

	$('amoff').observe('click', function (amoff) {
      var aTag = "[amoff]";
      var eTag = "[/amoff]";
			elText.focus();
			
      /* for Internet Explorer */
      if(typeof document.selection != 'undefined') {
        var range = document.selection.createRange();
        var insText = range.text;
        range.text = aTag + insText + eTag;

        range = document.selection.createRange();
        if (insText.length == 0) {
          range.move('character', -eTag.length);
        } else {
          range.moveStart('character', aTag.length + insText.length + eTag.length);      
        }
        range.select();
      }
      /* for Gecko based Browsers */
			else if(typeof elText.selectionStart != undefined)
			{
        var start = elText.selectionStart;
        var end = elText.selectionEnd;
        var insText = elText.value.substring(start, end);
        elText.value = elText.value.substr(0, start) + aTag + insText + eTag + elText.value.substr(end);

        var pos;
        if (insText.length == 0) {
          pos = start + aTag.length;
        } else {
          pos = start + aTag.length + insText.length + eTag.length;
        }
        elText.selectionStart = pos;
        elText.selectionEnd = pos;
      }
      /* for other Browsers */
      else
      {       
        var pos;
        var re = new RegExp('^[0-9]{0,3}$');
        while(!re.test(pos)) {
          pos = prompt("Insert at Position (0.." + elText.value.length + "):", "0");
        }
        if(pos > elText.value.length) {
          pos = elText.value.length;
        }
        var insText = prompt("Video Audio Link");
        elText.value = elText.value.substr(0, pos) + aTag + insText + eTag + elText.value.substr(pos);
  }


	});
});

Event.observe(window, 'load', function (ampl) {
	if($('message_new')) {
		var elText      = $('message_new'); 
	} else 	if($('message_old')) {
		var elText      = $('message_old'); 
	} else {
		var elText      = $('message');
	}

	$('ampl').observe('click', function (ampl) {
      var aTag = "[ampl]";
      var eTag = "[/ampl]";
			elText.focus();
			
      /* for Internet Explorer */
      if(typeof document.selection != 'undefined') {
        var range = document.selection.createRange();
        var insText = range.text;
        range.text = aTag + insText + eTag;

        range = document.selection.createRange();
        if (insText.length == 0) {
          range.move('character', -eTag.length);
        } else {
          range.moveStart('character', aTag.length + insText.length + eTag.length);      
        }
        range.select();
      }
      /* for Gecko based Browsers */
			else if(typeof elText.selectionStart != undefined)
			{
        var selectedText;
        var start = elText.selectionStart;
        var end = elText.selectionEnd;
        var insText = elText.value.substring(start, end);
        elText.value = elText.value.substr(0, start) + aTag + insText + eTag + elText.value.substr(end);

        var pos;
        if (insText.length == 0) {
          pos = start + aTag.length;
        } else {
          pos = start + aTag.length + insText.length + eTag.length;
        }
        elText.selectionStart = pos;
        elText.selectionEnd = pos;
      }
      /* for other Browsers */
      else
      {       
        var pos;
        var re = new RegExp('^[0-9]{0,3}$');
        while(!re.test(pos)) {
          pos = prompt("Insert at Position (0.." + elText.value.length + "):", "0");
        }
        if(pos > elText.value.length) {
          pos = elText.value.length;
        }
       
        var insText = prompt("MP3 Playlist Links");
        elText.value = elText.value.substr(0, pos) + aTag + insText + eTag + elText.value.substr(pos);
      }
	});
});
