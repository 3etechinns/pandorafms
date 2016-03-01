// Pandora FMS - http://pandorafms.com
// ==================================================
// Copyright (c) 2005-2010 Artica Soluciones Tecnologicas
// Please see http://pandorafms.org for full contribution list

// This program is free software; you can redistribute it and/or
// modify it under the terms of the  GNU Lesser General Public License
// as published by the Free Software Foundation; version 2

// This program is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.

/*-----------------------------------------------*/
/*-------------------Constants-------------------*/
/*-----------------------------------------------*/
var MAX_ZOOM_LEVEL = 50;

/*-----------------------------------------------*/
/*------------------Constructor------------------*/
/*-----------------------------------------------*/
var MapController = function(target) {
	this._target = target;
	
	this._id = $(target).data('id');
}

/*-----------------------------------------------*/
/*------------------Atributes--------------------*/
/*-----------------------------------------------*/
MapController.prototype._id = null;
MapController.prototype._viewport = null;
MapController.prototype._zoomManager = null;
MapController.prototype._slider = null;

/*-----------------------------------------------*/
/*--------------------Methods--------------------*/
/*-----------------------------------------------*/
/**
Function init_map
Return void
This function init the map
*/
MapController.prototype.init_map = function() {
	var self = this;
	
	var svg = d3.select(this._target + " svg");
	
	self._zoomManager =
		d3.behavior.zoom().scaleExtent([1/MAX_ZOOM_LEVEL, MAX_ZOOM_LEVEL]).on("zoom", zoom);
	
	self._viewport = svg
		.call(self._zoomManager)
		.append("g")
			.attr("class", "viewport");
	
	/**
	Function zoom
	Return void
	This function manages the zoom
	*/
	function zoom() {
		self.close_all_tooltips();
		
		var zoom_level = d3.event.scale;
		
		self._slider.property("value", Math.log(zoom_level));
		
		self._viewport
			.attr("transform", "translate(" + d3.event.translate + ")scale(" + d3.event.scale + ")");
	}
	
	/**
	Function zoom_in
	Return void
	This function zoom with "+" button
	*/
	function zoom_in(d) {
		var step = parseFloat(self._slider.property("step"));
		var slider_value = parseFloat(self._slider.property("value"));
		
		slider_value += step;
		
		var zoom_level = Math.exp(slider_value);
		
		self._slider.property("value", Math.log(zoom_level));
		self._slider.on("input")();
	}
	
	/**
	Function zoom_out
	Return void
	This function zoom with "-" button
	*/
	function zoom_out(d) {
		var step = parseFloat(self._slider.property("step"));
		var slider_value = parseFloat(self._slider.property("value"));
		
		slider_value -= step;
		
		var zoom_level = Math.exp(slider_value);
		
		self._slider.property("value", Math.log(zoom_level));
		self._slider.on("input")();
	}
	
	/**
	Function home_zoom
	Return void
	This function zoom with "H" button (reset zoom)
	*/
	function home_zoom(d) {
		self._zoomManager.scale(1).translate([0, 0]).event(self._viewport);
	}
	
	/**
	Function slided
	Return void
	This function manages the slide (zoom system)
	*/
	function slided(d) {
		var slider_value = parseFloat(self._slider.property("value"))
		
		zoom_level = Math.exp(slider_value);
		
		/*----------------------------------------------------------------*/
		/*-Code to translate the map with the zoom for to hold the center-*/
		/*----------------------------------------------------------------*/
		var center = [
			parseFloat(d3.select("#map").style('width')) / 2,
			parseFloat(d3.select("#map").style('height')) / 2];
		
		var old_translate = self._zoomManager.translate();
		var old_scale = self._zoomManager.scale();
		
		var temp1 = [(center[0] - old_translate[0]) / old_scale,
			(center[1] - old_translate[1]) / old_scale];
		
		var temp2 = [temp1[0] * zoom_level + old_translate[0],
			temp1[1] * zoom_level + old_translate[1]];
		
		var new_translation = [
			old_translate[0] + center[0] - temp2[0],
			old_translate[1] + center[1] - temp2[1]]
		
		self._zoomManager.scale(zoom_level)
			.translate(new_translation)
			.event(self._viewport);
	}
	
	self._slider = d3.select("#map .zoom_controller .vertical_range")
		.property("value", 0)
		.property("min", -Math.log(MAX_ZOOM_LEVEL))
		.property("max", Math.log(MAX_ZOOM_LEVEL))
		.property("step", Math.log(MAX_ZOOM_LEVEL) * 2 / MAX_ZOOM_LEVEL)
		.on("input", slided);
	
	
	d3.select("#map .zoom_box .home_zoom")
		.on("click", home_zoom);
	
	d3.select("#map .zoom_box .zoom_in")
		.on("click", zoom_in);
	
	d3.select("#map .zoom_box .zoom_out")
		.on("click", zoom_out);
	
	
	self.paint_nodes();
	
	this.init_events();
};

/**
Function paint_nodes
Return void
This function paint the nodes
*/
MapController.prototype.paint_nodes = function() {
	self = this;
	
	self._viewport.selectAll(".node")
		.data(
			nodes
				.filter(function(d, i) {
					if (d.type != ITEM_TYPE_EDGE_NETWORKMAP)
						return true;
					else return false;
				}))
			.enter()
				.append("g")
					.attr("transform",
						function(d) { return "translate(" + d['x'] + " " + d['y'] + ")";})
					.attr("class", "draggable node")
					.attr("id", function(d) { return "node_" + d['graph_id'];})
					.attr("data-id", function(d) { return d['id'];})
					.attr("data-graph_id", function(d) { return d['graph_id'];})
					.attr("data-type", function(d) { return d['type'];})
					.append("circle")
						.attr("style", "fill: rgb(50, 50, 128);")
						.attr("r", "6");
	
	
	//~ this._viewport.selectAll(".arrow").each(function(d) {
		//~ console.log(d);
	//~ });
	
	
	
	var arrow_layouts = self._viewport.selectAll(".arrow")
		.data(
			nodes
				.filter(function(d, i) {
					if (d.type == ITEM_TYPE_EDGE_NETWORKMAP)
						return true;
					else return false;
				}))
		.enter()
			.append("g")
				.attr("class", "arrow")
				.attr("id", function(d) { return "arrow_" + d['graph_id'];});
	
	create_arrow(arrow_layouts);
	
	function create_arrow(arrow_layouts) {
		
		arrow_layouts.each(function(d) {
			var arrow_layout = this;
			
			var node_arrow = edges.filter(function(d2) {
				if (d2['graph_id'] == d['graph_id'])
					return true;
				else
					return false;
			})[0];
			
			var to_node = nodes.filter(function(d2) {
				if (d2['graph_id'] == node_arrow['to'])
					return true;
				else
					return false;
			})[0];
			
			var from_node = nodes.filter(function(d2) {
				if (d2['graph_id'] == node_arrow['from'])
					return true;
				else
					return false;
			})[0];
			
			var id_arrow = d3.select(arrow_layout).attr("id");
			var id_node_to = "node_" + to_node['graph_id'];
			var id_node_from = "node_" + from_node['graph_id'];
			
			console.log("-----------");
			console.log(id_arrow);
			arrow_by_pieces(id_arrow, id_node_to, id_node_from);
		});
	}
	
}

function arrow_by_pieces(id_arrow, id_node_to, id_node_from) {
	if (typeof(step) === "undefined")
		step = 0;
	
	step++;
	
	var callback = "arrow_by_pieces("+
		"'" + id_arrow + "', "+
		"'" + id_node_to + "', "+
		"'" + id_node_from + "', "+
		" " + step + ");";
	
	/*-----------------------------------------------------------*/
	/*- Print by steps ------------------------------------------*/
	/*-  (beasue the browsers put async the symbols and ---------*/
	/*-   they has not the size) --------------------------------*/
	/*- 1 add the arrow body symbol -----------------------------*/
	/*- 2 add the arrow head symbol -----------------------------*/
	/*- 3 Resize and positing the arrow pieces ------------------*/
	/*-----------------------------------------------------------*/
	
	switch (step) {
		case 1:
			var arrow_layout = d3
				.select("#" + id_arrow);
			
			arrow_layout
				.style("opacity", 1);
			
			arrow_layout.append("g")
				.attr("class", "body")
				.append("use")
					.attr("xlink:href", "images/maps/body_arrow.svg#body_arrow")
					.attr("onload",
						function() {console.log("caca");});
						//~ callback);
			break;
		case 2:
			console.log("++++++++++");
			console.log(id_arrow);
			break;
	}
}

function arrow_by_pieces_old(id_arrow, element1, element2, step) {
	if (typeof(step) === "undefined")
		step = 0;
	
	step++;
	
	var callback = "arrow_by_pieces("+
		"'" + id_arrow + "', "+
		"'" + element1 + "', "+
		"'" + element2 + "', "+
		" " + step + ");"
	
	/*-----------------------------------------------*/
	/*---------------- Print by steps ---------------*/
	/*------------- 1 Link the arrow body -----------*/
	/*------------- 2 Link the arrow head -----------*/
	/*------------- 3 Print the arrow ---------------*/
	/*-----------------------------------------------*/
	switch (step) {
		case 1:
			var arrow = svg.append("g")
				.attr("id", id_arrow)
				.attr("style", "opacity: 0");
			
			arrow.append("g")
				.attr("id", "body")
				.append("use")
					.attr("xlink:href", "body_arrow.svg#body_arrow")
					.attr("onload",
						callback);
			break;
		case 2:
			var arrow = d3.select("#" +id_arrow);
			
			arrow.append("g")
					.attr("id", "head")
					.append("use")
						.attr("xlink:href", "head_arrow.svg#head_arrow")
						.attr("onload",
							callback);
			break;
		case 3:
			var c_elem1 = get_center_element("#" + element1);
			var c_elem2 = get_center_element("#" + element2);
			var distance = get_distance_between_point(c_elem1, c_elem2);
			
			var transform = d3.transform();
		
			/*---------------------------------------------*/
			/*--- Position of layer arrow (body + head) ---*/
			/*---------------------------------------------*/
			var arrow = d3.select("#" + id_arrow);
			
			var arrow_body = d3.select("#" + id_arrow + " #body");
			var arrow_body_b = arrow_body.node().getBBox();
			
			transform.translate[0] = c_elem1[0];
			transform.translate[1] = c_elem1[1] - arrow_body_b['height'] / 2;
			transform.rotate = get_angle_of_line(c_elem1, c_elem2);
			
			arrow.attr("transform", transform.toString());
			
			/*---------------------------------------------*/
			/*-------- Resize the body arrow width --------*/
			/*---------------------------------------------*/
			var arrow_body = d3.select("#" + id_arrow + " #body");
			var arrow_body_b = arrow_body.node().getBBox();
			var arrow_head = d3.select("#" + id_arrow + " #head");
			var arrow_head_b = arrow_head.node().getBBox();
			
			var body_width = distance - arrow_head_b['width'];
			
			transform = d3.transform();
			transform.scale[0] = body_width / arrow_body_b['width'];
			arrow_body.attr("transform", transform.toString());
			
			/*---------------------------------------------*/
			/*---------- Position of head arrow -----------*/
			/*---------------------------------------------*/
			transform = d3.transform();
			
			var arrow_body_t = d3.transform(arrow_body.attr("transform"));
			
			var scale = arrow_body_t.scale[0];
			var x = 0 + arrow_body_b['width'] * scale;
			var y = 0 + (arrow_body_b['height'] / 2  - arrow_head_b['height'] / 2);
			
			transform.translate[0] = x;
			transform.translate[1] = y;
			
			arrow_head.attr("transform", transform.toString());
			
			/*---------------------------------------------*/
			/*------- Show the result in one time ---------*/
			/*---------------------------------------------*/
			arrow.attr("style", "opacity: 1");
			break;
	}
}

/**
Function init_events
Return boolean
This function init click events in the map
*/
MapController.prototype.init_events = function(principalObject) {
	self = this;
	
	$(this._target + " svg *, " + this._target + " svg")
		.off("mousedown", {map: this}, this.click_event);

	d3.selectAll(".node")
		.on("mouseover", function(d) {
			d3.select("#node_" + d['graph_id'])
				.select("circle")
				.attr("style", "fill: rgb(128, 50, 50);");
		})
		.on("mouseout", function(d) {
			d3.select("#node_" + d['graph_id'])
				.select("circle")
				.attr("style", "fill: rgb(50, 50, 128);");
		})
		.on("click", function(d) {
			if (d3.event.defaultPrevented) return;
			
			self.tooltip_map_create(self, this);
		});
	
	var drag = d3.behavior.drag()
		.origin(function(d) { return d; })
		.on("dragstart", dragstarted)
		.on("drag", dragged)
		.on("dragend", dragended);
	
	d3.selectAll(".draggable").call(drag);
	
	function dragstarted(d) {
		d3.event.sourceEvent.stopPropagation();
		
		if ($("#node_" + d['graph_id']).hasClass("tooltipstered")) {
			$("#node_" + d['graph_id']).tooltipster('destroy');
		}
		
		d3.select(this).classed("dragging", true);
	}
	
	function dragged(d) {
		var delta_x = d3.event.dx;
		var delta_y = d3.event.dy;
		
		var translation = d3.transform(d3.select(this).attr("transform"));
		scale = 1;
		var x = translation.translate[0] + delta_x;
		var y = translation.translate[1] + delta_y;
		
		d3.select(this).attr("transform",
			"translate(" + x + " " + y + ")");
	}
	
	function dragended(d) {
		d3.select(this).classed("dragging", false);
		
		if ($("#node_" + d['graph_id']).hasClass("tooltipstered")) {
			$("#node_" + d['graph_id']).tooltipster('destroy');
		}
	}
}

/**
Function tooltip_map_create
Return void
This function manages nodes tooltips
*/
MapController.prototype.tooltip_map_create = function(self, target) {
	var nodeTarget = $(target);
	var spinner = $('#spinner_tooltip').html();
	
	var nodeR = parseInt($("circle", nodeTarget).attr("r"));
	nodeR = nodeR * self._zoomManager.scale(); // Apply zoom
	var node_id = nodeTarget.attr("id");
	
	var type = parseInt(nodeTarget.data("type"));
	var data_id = parseInt(nodeTarget.data("id"));
	var data_graph_id = parseInt(nodeTarget.data("graph_id"));
	
	nodeTarget.tooltipster({
		arrow: true,
		trigger: 'click',
		contentAsHTML: true,
		autoClose: false,
		offsetX: nodeR,
		theme: 'tooltipster-noir',
		multiple: true,
		interactive: true,
		content: spinner,
		restoration: 'none',
		functionBefore: function(origin, continueTooltip) {
			continueTooltip();
			self.nodeData(data_id, type, self._id, data_graph_id, origin, node_id);
		}
	});
	
	nodeTarget.tooltipster("show");
}

/**
Function close_all_tooltips
Return void
This function hide nodes tooltips
*/
MapController.prototype.close_all_tooltips = function() {
	$("svg .tooltipstered").tooltipster("destroy");
}

/**
Function nodeData
Return array(data)
This function returns the data of the node
*/
MapController.prototype.nodeData = function(data_id, type, id_map, data_graph_id, origin, node_id) {
	var params = {};
	params["getNodeData"] = 1;
	params["id_node_data"] = data_id;
	params["type"] = type;
	params["id_map"] = id_map;
	params["data_graph_id"] = data_graph_id;
	params["node_id"] = node_id;
	params["page"] = "include/ajax/map.ajax";
	
	jQuery.ajax ({
		data: params,
		dataType: "json",
		type: "POST",
		url: "ajax.php",
		success: function (data) {
			if ($(origin).hasClass("tooltipstered")) {
				origin.tooltipster('content', data);
			}
		}
	});
}

/*-----------------------------------------------*/
/*-------------------Functions-------------------*/
/*-----------------------------------------------*/

/**
Function open_in_another_window
Return void
This function open the node in extra window
*/
function open_in_another_window(link) {
	window.open(link);
}

function close_button_tooltip(data_graph_id) {
	$("#node_" + data_graph_id).tooltipster("destroy");
}

caca = null;

function tooltip_to_new_window(data_graph_id) {
	var content = $("#tooltip_" + data_graph_id + " .body").html();
	
	$("#node_" + data_graph_id).tooltipster("destroy");
	
	var window_popup = window.open("", "window_" + data_graph_id,
		'title=MIERDACA, width=300, height=300, toolbar=no, location=no, directories=no, status=no, menubar=no');
	
	$(window_popup.document.body).html(content);
}