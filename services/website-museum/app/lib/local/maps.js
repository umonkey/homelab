jQuery(function ($) {
  var default_ll = [56.27740751089426, 28.484826698302722];

  var create_map = function (div_id) {
    var osm_layer = L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
      maxZoon: 18,
      attribution: 'Map data © <a href="http://openstreetmap.org">OpenStreetMap</a> contributors'
    });

    var osmfr_layer = L.tileLayer('http://a.tile.openstreetmap.fr/osmfr/{z}/{x}/{y}.png', {
      maxZoon: 20,
      attribution: 'Map data © <a href="http://openstreetmap.org">OpenStreetMap</a> contributors'
    });

    // UNOFFICIAL HACK.
    // http://stackoverflow.com/questions/9394190/leaflet-map-api-with-google-satellite-layer
    var google_layer = L.tileLayer('http://{s}.google.com/vt/lyrs=m&x={x}&y={y}&z={z}', {
      maxZoom: 20,
      subdomains: ['mt0', 'mt1', 'mt2', 'mt3']
    });

    var google_hybrid_layer = L.tileLayer('http://{s}.google.com/vt/lyrs=s,h&x={x}&y={y}&z={z}', {
      maxZoom: 20,
      subdomains: ['mt0', 'mt1', 'mt2', 'mt3']
    });

    var map = L.map(div_id, {
      layers: [osm_layer],
      loadingControl: true,
      fullscreenControl: true
    });

    L.control.layers({
      "OpenStreetMap": osm_layer,
      // "OSM France (больше зум)": osmfr_layer,
      "Google (спутник)": google_hybrid_layer
    }).addTo(map);

    return map;
  };

  $(".map").each(function () {
    var div = $(this);
    if (!div.attr("id"))
      return;

    var source = div.attr("data-src");
    var center = div.attr("data-center");
    var data_var = div.attr("data-var");
    var zoom = parseInt(div.attr("data-zoom") || 13);

    if (source) {
      $.ajax({
        url: source,
        dataType: "json"
      }).done(function (res) {
        res = $.extend({
          markers: []
        }, res);

        var map = create_map(div.attr("id"));

        var points = [];
        var cluster = L.markerClusterGroup();

        for (var idx in res.markers) {
          var tree = res.markers[idx];
          if (tree.latlng) {
            points.push(tree.latlng);

            var m = L.marker(tree.latlng);
            m.addTo(cluster);

            var html = "<p><a href='" + tree.link + "'>" + tree.title + "</a></p>";
            m.bindPopup(html);
          }
        }

        map.addLayer(cluster);

        var bounds = L.latLngBounds(points);
        map.fitBounds(bounds);
      });
    }

    else if (center) {
      var parts = center.split(/,\s*/);
      if (parts.length != 2)
        parts = default_ll;

      var latlng = [parseFloat(parts[0]), parseFloat(parts[1])];
      var map = create_map(div.attr("id"));

      var marker = L.marker(latlng, {
        draggable: div.hasClass("dragable"),
      });
      marker.addTo(map);

      marker.on("dragend", function (e) {
        var ll = marker.getLatLng();

        var cid = div.attr("data-for-lat");
        if (cid)
          $(cid).val(ll.lat)

        cid = div.attr("data-for-lon");
        if (cid)
          $(cid).val(ll.lng)
      });

      map.setView(latlng, zoom);
    }

    else if (data_var) {
      var data = window[data_var];

      var map = create_map(div.attr("id"));

      var points = [];
      var cluster = L.markerClusterGroup();

      for (var idx in data.places) {
        var p = data.places[idx];
        points.push(p.latlng);

        var m = L.marker(p.latlng);
        m.addTo(cluster);

        m.bindPopup(p.html);
      }

      map.addLayer(cluster);

      var bounds = L.latLngBounds(points);
      map.fitBounds(bounds);
    }
  });
});

// vim: set ts=2 sts=2 sw=2 et:
