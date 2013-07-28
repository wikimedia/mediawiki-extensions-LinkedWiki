function lwFlowChart($canvas,graphStr){
  this.tabLines = new Array();
  this.margemin = 10;
  this.margex = 10;
  this.margey = 10;
  this.a = $canvas[0].getContext('2d');
  this.ratio = 0;
  
  //BEGIN CONSTRUCTOR
  var regLine = /(?:=(?:(~*)\[\[([^\]\|]*)(?:\|([^\]]*))?\]\])?=>)?(~*)\[\[([^\]\|]*)(?:\|([^\]]*))?\]\]/g;
  
  //Read shape arrow and rect
  //console.log(graphStr);
  var lines = graphStr.split("\n");  
  for(i in lines){
    var line  = lines[i].trim();
    //console.log(lines[i]); 
    if(line != ""){
      var flowChartLine = new lwFlowChartLine();
      var first = true;
      while ((myArray = regLine.exec(line)) !== null)
      {
	//var msg = myArray[0]+"\n";
	//msg += "1 Exist property page: " +  myArray[1] +"\n";
	//msg += "2 property title page: " +  myArray[2] +"\n";
	//msg += "3 property label : " +  myArray[3]+"\n";
	//msg += "4 Exist object page : " +  myArray[4]+"\n";
	//msg += "5 object title page : " +  myArray[5]+"\n";
	//msg += "6 object label : " +  myArray[6]+"\n";
	//console.log(msg);
	if(!first){
	  if(myArray[1] != undefined){
	    flowChartLine.addShape(new lwFlowChartShape(myArray[2],myArray[3],myArray[1]!="~",true));
	  }else{
	    flowChartLine.addShape(new lwFlowChartShape(null,null,null,true));
	  }
	}
	flowChartLine.addShape(new lwFlowChartShape(myArray[5],myArray[6],myArray[4]!="~",false));
	first = false;
      }
      this.tabLines.push(flowChartLine);      
    }      
  }

  //Calculate size
  //console.log(this.tabLines);
  var heightLine = 60;
  var nbLine = 0;
  for(var i in this.tabLines){
      var line = this.tabLines[i];     
      for(var j in line.shapes){
	var shape = line.shapes[j];
	shape.label = shape.label == null ? shape.title :  shape.label;
	if(shape.isArrow){
	  shape.width = $canvas.lwDrawLineArrow(shape.label,"red",0,0,0,0,false); 
	}else{
	  shape.width = $canvas.lwDrawRect(shape.label,"red",0,0,false);  
	}
	shape.height = 40;
	shape.centery= heightLine*nbLine + heightLine/2;
	var beforex = 0;
	if(shape.pointerShapesBefore.length > 0){
	   beforex = shape.pointerShapesBefore[0].rightx ;
	}
	shape.centerx= beforex + shape.width/2;
	
	shape.rightx= shape.centerx + shape.width/2;
	shape.righty= shape.centery;
	
	shape.leftx= shape.centerx - shape.width/2;
	shape.lefty= shape.centery;
	
	shape.topx=shape.centerx;
	shape.topy=shape.centery - heightLine/2;
	shape.downx=shape.centerx;
	shape.downy=shape.centery + heightLine/2;
	
	if(nbLine == 0)
	  shape.fixed=true;
	
      }
      nbLine++;
  }
  ///////////////
    
  //Build array shape fixed and to link shapes 
  var arrayShapeFixed = this.tabLines[0].shapes;
  for(var iline = 1 ; iline < (this.tabLines.length) ; iline++){
      for(var i = 0; i < arrayShapeFixed.length; i++){
	    var shapeFixed = arrayShapeFixed[i];
	    for(l in this.tabLines){
		var line = this.tabLines[l];     
		for(j in line.shapes){
		  var shape = line.shapes[j];
		  if(!shape.fixed && !shape.isArrow && shape.title == shapeFixed.title){
		    for(var b = 0; b < shape.pointerShapesAfter.length; b++){
		      shape.pointerShapesAfter[b].leftx = shapeFixed.rightx;
		      shape.pointerShapesAfter[b].lefty = shapeFixed.righty;
		      shape.pointerShapesAfter[b].removeShapeBefore(shape);
		      shape.pointerShapesAfter[b].addShapeBefore(shapeFixed);
		      shapeFixed.addShapeAfter(shape.pointerShapesAfter[b]);
		    }
		    for(var a = 0; a <  shape.pointerShapesBefore.length; a++){
		      shape.pointerShapesBefore[a].rightx = shapeFixed.leftx;
		      shape.pointerShapesBefore[a].righty = shapeFixed.lefty;
		      shape.pointerShapesBefore[a].removeShapeAfter(shape);
		      shape.pointerShapesBefore[a].addShapeAfter(shapeFixed);		      
		      shapeFixed.addShapeBefore(shape.pointerShapesBefore[a]);
		    }
		     line.shapes.splice(j,1);
		  }		  
		} 
	    }
      }
      for(var s in this.tabLines[iline].shapes)
	this.tabLines[iline].shapes[s].fixed = true;
      arrayShapeFixed = arrayShapeFixed.concat(this.tabLines[iline].shapes);
      //console.log(arrayShapeFixed);
  }

  //Calculate the good position
   for(var s in arrayShapeFixed){
	var shape = arrayShapeFixed[s];
	shape.checkPlace();
    } 
      
    //calc size of the graph
    var xmax = 0;
    var ymax = 0;
   for(var s in arrayShapeFixed){
	var shape = arrayShapeFixed[s];
	if(shape.rightx > xmax)
	   xmax = shape.rightx;
	if(shape.downy > ymax)
	   ymax = shape.downy;
    } 
    
  //resize the graph in the canvas
  var ratiox = $canvas[0].width / (this.margemin*2 + xmax);
  var ratioy = $canvas[0].height / (this.margemin*2 +ymax );
    //console.log("ratiox"+ratiox);
    //console.log("ratioy"+ratioy);
  
    this.a.save();
    if(ratiox<ratioy)
    {
      this.ratio = ratiox;
    this.a.scale(this.ratio,this.ratio);
      this.margex = this.margemin;
      this.margey = ($canvas[0].height - ymax*ratiox)/2;
    }
    else
    {
      this.ratio = ratioy;
    this.a.scale(this.ratio,this.ratio);
      this.margex = ($canvas[0].width - xmax*ratioy)/2 ;
      this.margey = this.margemin;
    }       
    
    this.a.translate(this.margex ,this.margey );
    
   // a.beginPath();
   // a.rect(0, 0, xmax, ymax);
   // a.lineWidth = 2;
   // a.strokeStyle = 'red';
   // a.stroke();  
    
  ///////////////
    
  //draw the graph
  for(i in this.tabLines){
      var line = this.tabLines[i];     
      for(j in line.shapes){
	var shape = line.shapes[j];
	var color = shape.isPageExist ? "black" : "red";
	if(shape.isArrow){
	  shape.width = $canvas.lwDrawLineArrow(
	    shape.label,
	    color,
	    shape.leftx,
	    shape.lefty,
	    shape.rightx,
	    shape.righty,
	    true); 
	}else{
	  $canvas.lwDrawRect(shape.label,color,shape.centerx,shape.centery,true);     
	}
      }
   }
   
  this.a.restore();
  
  //END CONSTRUCTOR
  
  //return the shape without the pointer
  this.getShape= function (x,y)
  {
  	for(i in this.tabLines){
	    var line = this.tabLines[i];     
	    for(j in line.shapes){
	      var shape = line.shapes[j];
	      var xmin = shape.leftx * this.ratio + this.margex ;
	      var xmax = shape.rightx * this.ratio + this.margex ;
	      var ymin = shape.topy * this.ratio + this.margey ;
	      var ymax = shape.downy * this.ratio + this.margey ;
		if(x > xmin && x < xmax && y  > ymin && y < ymax )
		  return shape;
	     }
	}
	return null;
  }
}

function lwFlowChartLine()
{
  this.shapes=new Array();
  this.addShape= function (shape)
  {
    if(this.shapes[this.shapes.length-1]!=undefined){
	var shapeBefore =  this.shapes[this.shapes.length-1];
      shape.addShapeBefore(shapeBefore);
      shapeBefore.addShapeAfter(shape);
    }
    this.shapes.push(shape);
  }
}

function lwFlowChartShape(title,label,isPageExist,isArrow)
{
  this.centerx=0;
  this.centery=0;
  this.topx=0;
  this.topy=0;
  this.rightx=0;
  this.righty=0;
  this.downx=0;
  this.downy=0;
  this.leftx=0;
  this.lefty=0;
  this.width=0;
  this.height=0;
  this.title=title;
  this.label=label;
  this.isPageExist=isPageExist;
  this.isArrow=isArrow;
  this.fixed=false;
  this.pointerShapesBefore=new Array();
  this.pointerShapesAfter=new Array();

  this.addShapeBefore= function (shape)
  {
    this.pointerShapesBefore.push(shape);
  }

  this.addShapeAfter= function (shape)
  {
    this.pointerShapesAfter.push(shape);
  }
  
  this.removeShapeBefore= function (shape)
  {
      for(var i = 0; i < this.pointerShapesBefore.length; i++) {
	  if(this.pointerShapesBefore[i] === shape) {
	    this.pointerShapesBefore.splice(i, 1);
	  }
      }
  }

  this.removeShapeAfter= function (shape)
  {
      for(var i = 0; i < this.pointerShapesAfter.length; i++) {
	  if(this.pointerShapesAfter[i] === shape) {
	    this.pointerShapesAfter.splice(i, 1);
	  }
      }
  }  
   
  this.setLeftx= function (x)
  {    
	  this.leftx = x ;
	  this.rightx = x + this.width;
	  this.centerx = this.leftx + this.width/2;	
	  this.topx=this.centerx;
	  this.downx=this.centerx;
  }
  this.setRightx= function (x)
  {    
	  this.leftx = x - this.width;
	  this.rightx = x ;
	  this.centerx = this.leftx + this.width/2;	
	  this.topx=this.centerx;
	  this.downx=this.centerx;
  }
  
  this.checkPlace= function ()
  {
     if(this.leftx > (this.rightx - this.width) ){
	this.setLeftx(this.leftx);	
	
	for(var a in this.pointerShapesAfter){
		  this.pointerShapesAfter[a].leftx = this.rightx;	
	}
	for(var b in this.pointerShapesBefore){
	  this.pointerShapesBefore[b].rightx = this.leftx;	
	}
	
	for(var a in this.pointerShapesAfter){
	  this.pointerShapesAfter[a].checkPlace();
	}
	for(var b in this.pointerShapesBefore){
	  this.pointerShapesBefore[b].checkPlace();
	}
    }
  }  
}


$( function () {

     function getMousePos(canvas, evt) {
        var rect = canvas.getBoundingClientRect();
        return {
          x: evt.clientX - rect.left,
          y: evt.clientY - rect.top
        };
      }
      
      var $elsCanvas = $("canvas.lwgraph-flow");
           
      $elsCanvas.each(function(index, canvas) {
      canvas.width = parseInt($(this).css('width'));
      canvas.height =  parseInt($(this).css('height'));	      
 
        var graphFlow = new lwFlowChart($(this),$(this).text());
	canvas.addEventListener('click', function(evt) {
              var mousePos = getMousePos(canvas, evt);
	      var shape = graphFlow.getShape(mousePos.x ,mousePos.y)
	      if( shape != null){
		 var url = mw.config.get( 'wgScript' ) + '?title=' + encodeURIComponent(shape.title) ;
		if(shape.isPageExist)
		  window.location.href = url;
		else		  
		  window.location.href = url+"&action=edit&redlink=1";
	      }
	});
	
	canvas.addEventListener('mousemove', function(evt) {
	    var mousePos = getMousePos(canvas, evt);
	    var shape = graphFlow.getShape(mousePos.x ,mousePos.y)
	    if( shape != null)
		$(this).css('cursor', 'pointer');
	    else
	      $(this).css('cursor', 'default');
	  }, false);
      });	    
}); 