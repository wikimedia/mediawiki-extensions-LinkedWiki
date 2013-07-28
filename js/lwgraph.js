jQuery.fn.lwDrawPolygon = function(shape) {
    var a = $(this)[0].getContext('2d');
        
    a.beginPath();
    a.moveTo(shape[0][0],shape[0][1]);
    for(p in shape)
    if (p > 0) a.lineTo(shape[p][0],shape[p][1]);
    a.lineTo(shape[0][0],shape[0][1]);
    a.fill();

};

function lwTranslateShape(shape,x,y) {
    var rv = [];
    for(p in shape)
    rv.push([ shape[p][0] + x, shape[p][1] + y ]);
    return rv;

};

function lwRotateShape(shape,ang) {
  
    function rotatePoint(ang,x,y) {
	return [
		(x * Math.cos(ang)) - (y * Math.sin(ang)),
		(x * Math.sin(ang)) + (y * Math.cos(ang))
		];
    };
  
    var rv = [];
    for(p in shape)
    rv.push(rotatePoint(ang,shape[p][0],shape[p][1]));
    return rv;

};

jQuery.fn.lwDrawLineArrow= function(text,color,x1,y1,x2,y2,draw) {
    var a = $(this)[0].getContext('2d');
    var arrow = [
	[ 2, 0 ],
	[ -10, -4 ],
	[ -10, 4]
	];
      var marge = 10 ;
      var height = 14;
      var width = 0;
    if(text !=null){
      a.save();

	a.font = '14px Arial';
	var metrics = a.measureText(text);
	width = metrics.width;
	
	var cx = x1 > x2 ? x2 + x1 - x2 : x1 + x2 - x1;
	var cy = y1 > y2 ? y2 + y1 - y2 : y1 + y2 - y1;
	//a.translate(x1 - width/2 - marge -5 + x2-x1,y1 - height/2 + y2-y1);
	a.translate(cx- width/2 - marge -5 ,cy - height/2 );
	a.textAlign = 'center';
	    a.textBaseline = 'middle';
	    a.fillStyle = color;      
	    a.fillText(text, 0,0); 
      a.restore();
    }
    if(draw){
	a.beginPath();
	a.moveTo(x1,y1);
	a.lineTo(x2,y2);
	a.stroke();
	var ang = Math.atan2(y2-y1,x2-x1);
	$(this).lwDrawPolygon(lwTranslateShape(lwRotateShape(arrow,ang),x2,y2));
    }
    
    return width + marge*2;
};


jQuery.fn.lwDrawRect= function(text,color,x,y,draw) {
    var a = $(this)[0].getContext('2d');
    var rect = [[-30,30],[-30,-30],[30,-30],[30,30],[-28,30],[-28,28],[28,28],[28,-28],[-28,-28],[-28,30]];
    var marge = 15;
    a.save();

      a.translate(x,y);
      var height = 20;
      a.font = '20px Arial';
      var metrics = a.measureText(text);
      
      var width = metrics.width;   
      if(draw){
	  for(var i = 0; i < rect.length ; i++){
	    //console.log(rect[i][0]);
	    //console.log(rect[i][1]);
	    rect[i][0] = rect[i][0]>0? rect[i][0] -(30-marge) + width/2: rect[i][0]+(30-marge) - width/2 ;
	    rect[i][1] = rect[i][1]>0? rect[i][1] -(30-marge) + height/2: rect[i][1]+(30-marge) - height/2 ;
	  }
	  $(this).lwDrawPolygon(lwTranslateShape(rect,0,0));
	  a.textAlign = 'center';
	  a.textBaseline = 'middle';
	  a.fillStyle = color;      
	  a.fillText(text, 0,0); 
      }
     a.restore();

    return width + 30;
    
};

// $( function () {
//     // This code must not be executed before the document is loaded. 
// 
//      function getMousePos(canvas, evt) {
//         var rect = canvas.getBoundingClientRect();
//         return {
//           x: evt.clientX - rect.left,
//           y: evt.clientY - rect.top
//         };
//       }
//       
//       var $elsCanvas = $("canvas.lwgraph-flow");      
//       $elsCanvas.css("border","2px solid black");      
// 
//       $elsCanvas.each(function(index, canvas) {
// 	  canvas.width = parseInt($(this).css('width'));
// 	  canvas.height =  parseInt($(this).css('height'));
// 	
//           console.log( index + ": " + $(this).text() );
// 	
// 	  canvas.addEventListener('click', function(evt) {
// 		var mousePos = getMousePos(canvas, evt);
// 		var shape = graphXXXX.getShape(mousePos.x ,mousePos.y)
// 		if( shape != null){
// 		  var url = mw.config.get( 'wgScript' ) + '?title=' + encodeURIComponent(shape.title) ;
// 		  if(shape.isPageExist)
// 		    window.location.href = url;
// 		  else		  
// 		    window.location.href = url+"&action=edit&redlink=1";
// 		}
// 	  });
// 	
// 	canvas.addEventListener('mousemove', function(evt) {
// 		  var mousePos = getMousePos(canvas, evt);
// 		  //console.log('Mouse position: ' + mousePos.x + ',' + mousePos.y);
// 		  var shape = graphXXXX.getShape(mousePos.x ,mousePos.y)
// 		  if( shape != null)
// 		      $(this).css('cursor', 'pointer');
// 		  else
// 		    $(this).css('cursor', 'default');
// 		}, false);
// 	
// 
// 	//$(this).lwDrawPolygon(lwTranslateShape(lwRotateShape(shape,Math.PI ),50,50));
// 	//$(this).lwDrawLineArrow(0,0,250,50);
// 	//$(this).lwDrawRect("tteteteteoto",250,50);  
// 	
//       });
// 	    
// });
