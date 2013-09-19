$(document).ready(function($){
	
	var col = 2;
	var row = 2;
	
	var makeCol = '<td><textarea cols="15" rows="3"></textarea></td>';
	var makeRow = '<tr><td><input type="checkbox" /> TH</td></tr>';
	
	var blocker = 0;
	
	// add column
	$('#addCol').live('click', function() {
		if(blocker > 0) {
			alert('Please remove all table head markings.');
		}
		else {
		col++;
		$('#container table tr').append(makeCol);
		}
	});
	
	// remove column until 2 left
	$('#delCol').live('click', function() {
		if(blocker > 0) {
			alert('Please remove all table head markings.');
		}
		else {
			if(col > 2) {
				$('#container table td:nth-child('+col+')').remove();
				col--;
			}
		}
	});
	
	// add row
	$('#addRow').live('click', function() {
		row++;
		var colCounter = col;
		
		$('#container table').append(makeRow);
		
		while(colCounter > 0) {
			$('#container table tr:last').append(makeCol);
			colCounter--;
		}
	});
	
	// remove row until 2 left
	$('#delRow').live('click', function() {
		if(row > 2) {
			$('#container table tr:last').remove();
			row--;
		}
	});
	
	// make tablehead
	$('input[type=checkbox]').live('click', function() {
		if($(this).parent().siblings().is('td')) {
			$(this).parent().siblings().replaceWith('<th><input type="text" size="15" /></th>');
			blocker++;
		}
		else {
			$(this).parent().siblings().replaceWith('<td><textarea cols="15" rows="3"></textarea></td>');
			blocker--;
		}
	});
	
	// generate code
	$('#tabCreate').live('click', function() {
		
		var width = $('#tabWidth').val();
		if(width == "") {
			var width = 95;
		}
		
		$('#container textarea').each(function() {
							var textarea = $(this);
							textarea.replaceWith(textarea.val());
						});
		
		$('#container input[type="text"]').each(function() {
							var input = $(this);
							input.replaceWith(input.val());
						});
						
		var oldValue = $('#container').clone().html().replace(/\r\n|\r|\n/g, '') //new line
													.replace(/\t/g, '') //tab
													.replace(/<td><input type="checkbox"> TH<\/td>/g, '')
													.replace(/<td class="deleteMe"><\/td>/g, '')
													.replace(/<table>/g, '[table='+width+']')
													.replace(/<\/table>/g, '[\/table]')
													.replace(/<tr>/g, '[tr]')
													.replace(/<\/tr>/g, '[\/tr]')
													.replace(/<th>/g, '[th]')
													.replace(/<\/th>/g, '[\/th]')
													.replace(/<td>/g, '[td]')
													.replace(/<\/td>/g, '[\/td]')
													.replace(/<tbody>/g, '')
													.replace(/<\/tbody>/g, '');
													
		$('#container table').replaceWith('<textarea>');
		$('#container textarea').attr('cols','70').attr('rows','15').text(oldValue);
	});
	
	// close window
	$('#tabClose').live('click', function() {
		window.close();
		return false;
	});
});