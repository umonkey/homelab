/**
 * Process a map div.
 **/
function ufw_map(div)
{
    var items = div.data('items');
    cluster_map(div.attr('id'), items);
};


function create_map(div_id)
{
  var osm_layer = L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
    maxZoom: 18,
    attribution: 'Map data © <a href="http://openstreetmap.org">OSM</a> contributors'
  });

  /*
  var osmfr_layer = L.tileLayer('http://a.tile.openstreetmap.fr/osmfr/{z}/{x}/{y}.png', {
    maxZoom: 20,
    attribution: 'Map data © <a href="http://openstreetmap.org">OpenStreetMap</a> contributors'
  });
  */

  // UNOFFICIAL HACK.
  // http://stackoverflow.com/questions/9394190/leaflet-map-api-with-google-satellite-layer
  /*
  var google_layer = L.tileLayer('http://{s}.google.com/vt/lyrs=m&x={x}&y={y}&z={z}', {
    maxZoom: 19,
    subdomains: ['mt0', 'mt1', 'mt2', 'mt3']
  });
  */

  var google_hybrid_layer = L.tileLayer('http://{s}.google.com/vt/lyrs=s,h&x={x}&y={y}&z={z}', {
    maxZoom: 19,
    subdomains: ['mt0', 'mt1', 'mt2', 'mt3']
  });

  var map = L.map(div_id, {
    layers: [osm_layer],
    loadingControl: true,
    fullscreenControl: true,
    scrollWheelZoom: false
  });

  L.control.layers({
    "OpenStreetMap": osm_layer,
    // "OSM France (больше зум)": osmfr_layer,
    "Google Satellite": google_hybrid_layer
  }).addTo(map);

  map.on("focus", function () {
      map.scrollWheelZoom.enable();
  });

  map.on("blur", function () {
      map.scrollWheelZoom.disable();
  });

  return map;
};


function cluster_map(div_id, markers)
{
    var points = [];
    var cluster = L.markerClusterGroup();

    console.log(markers);

    for (var idx in markers) {
        var m = $.extend({
            ll: null,
            html: null,
            link: null,
            title: null,
            image: null,
            icon: 'undefined'
        }, markers[idx]);

        if (m.ll) {
            points.push(m.ll);

            var m2 = L.marker(m.ll);
            m2.addTo(cluster);

            var html = null;
            if (m.html !== null) {
                html = m.html;
            } else {
                if (m.link && m.title)
                    html = sfmt("<p><a href='{0}'>{1}</a></p>", m.link, m.title);
                else if (m.title)
                    html = sfmt("<p>{0}</p>", m.title);

                if (m.image) {
                    html += sfmt("<p><a href='{0}'><img src='{1}' width='300'/></a></p>", m.link, m.image);
                }
            }

            if (html !== null) {
                m2.bindPopup(html);
            }

            var i = L.icon({
                iconUrl: '/map-icons/' + m.icon + '.png',
                iconSize: [32, 37],
                iconAnchor: [15, 37]
            });
            m2.setIcon(i);
        }
    }

    if (points.length == 0) {
        $('#' + div_id).html('<p>The map is empty.</p>');
        return;
    } else {
        $('#' + div_id).html('');
    }

    var map = create_map(div_id);
    map.addLayer(cluster);

    if (markers.length > 1) {
        var bounds = L.latLngBounds(points);
        map.fitBounds(bounds);
    } else {
        var zoom = ('zoom' in markers[0]) ? parseInt(markers[0].zoom) : 12;
        map.setView(markers[0].ll, zoom);
    }

    map.on("click", function (e) {
        if (div_id == 'testmap') {
            var ll = sfmt("{0},{1}", e.latlng.lat, e.latlng.lng);
            var html = sfmt("<div class='map' data-center='{0}'></div>", ll);
            $("pre:first code").text(html);
        } else if (e.originalEvent.ctrlKey) {
            var ll = sfmt("{0},{1}", e.latlng.lat, e.latlng.lng);
            var html = sfmt("<div class=\"map\" data-center=\"{0}\"></div>", ll);
            console.log("map center: " + ll);
            console.log("map html: " + html);
        }
    });
};
