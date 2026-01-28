/* lib/local/common.js */ // String formatting.
// https://stackoverflow.com/a/4673436/371526
if (!String.prototype.format) {
  String.prototype.format = function() {
    var args = arguments;
    return this.replace(/{(\d+)}/g, function(match, number) { 
      return typeof args[number] != 'undefined'
        ? args[number]
        : match
      ;
    });
  };
}


/* lib/local/async.js */ /**
 * Asynchronous forms with upload progress.
 *
 * Supports: form.async_files, form input.autosubmit, a.async,
 * form.async
 **/
jQuery(function ($) {
    var myalert = function (msg) {
        var msgbox = $("#msgbox");
        if (msgbox.length == 1) {
            msgbox.html(msg);
            msgbox.show();
        } else {
            alert(msg);
        }
    };

    window.handle_json = function (res) {
        res = $.extend({
            message: null,
            confirm: null,
            next: null,
            redirect: null,
            tab_open: null,
            refresh: false,
            trigger: null,
            add_class: null,
            reset: false,
            call: null,
            call_args: {}
        }, res);

        var blur = true;

        if (res.add_class)
            $("body").addClass(res.add_class);

        if (res.message)
            myalert(res.message);

        if (res.trigger)
            $(document).trigger(res.trigger);

        // WTF, there has to be a better way.
        if (res.reset)
            $(".reset").val("");

        if (res.confirm && res.next) {
            if (!confirm(res.confirm)) {
                $("body").removeClass("wait");
                return;
            }

            $.ajax({
                url: res.next,
                type: "GET",
                dataType: "json"
            }).done(function (res) {
                handle_json(res);
            }).fail(handle_ajax_failure);

            return;
        }

        else if (res.refresh) {
            window.location.reload();
            blur = false;
        } else if (res.redirect) {
            window.location.href = res.redirect;
            blur = false;
        } else if (res.tab_open) {
            window.open(res.tab_open, "_blank");
        }

        if (blur)
            $("body").removeClass("wait");

        if (res.call && res.call_args)
            window[res.call](res.call_args);
        else if (res.call)
            window[res.call]();
    };

    window.handle_ajax_failure = function (xhr, status, message) {
        if (xhr.status == 404)
            myalert("Form handler not found.");
        else if (message == "Debug Output")
            myalert(xhr.responseText);
        else if (status == "error" && message == "")
            ;  // aborted, e.g. F5 pressed
        else if (xhr.responseText)
            myalert("Request failed." + xhr.responseText);
        else
            myalert("Request failed: " + message + "\n\n" + xhr.responseText);

        $("body").removeClass("wait");
    };

    $(document).on("submit", "form.async", function (e) {
        var wait = $(this).hasClass("wait");
        if (wait)
            $("body").addClass("wait");

        $("#msgbox").hide();

        if (window.FormData === undefined) {
            // myalert("File uploads not supported by your browser.");
        } else {
            var fd = new FormData($(this)[0]);
            fd.append("_random", Math.random());

            var show_progress = function (percent, loaded, total) {
                if (total >= 102400) {
                    var mbs = function (bytes) { return Math.round(bytes / 1048576 * 100) / 100; };
                    var label = mbs(loaded) + " MB / " + mbs(total) + " MB";
                    $(".progressbar .label").html(label);

                    $(".progressbar .done").css("width", parseInt(percent) + "%");
                    $(".progressbar").show();
                }
            };

            $.ajax({
                url: $(this).attr("action"),
                type: "POST",
                data: fd,
                processData: false,
                contentType: false,
                cache: false,
                dataType: "json",
                xhr: function () {
                    var xhr = $.ajaxSettings.xhr();
                    xhr.upload.onprogress = function (e) {
                        var pc = Math.round(e.loaded / e.total * 100);
                        show_progress(pc, e.loaded, e.total);
                    };
                    return xhr;
                }
            }).done(function (res) {
                handle_json(res);
            }).always(function () {
                $(".progressbar").hide();
            }).fail(handle_ajax_failure);

            e.preventDefault();
        }
    });

    $(document).on("change", "form input.autosubmit", function (e) {
        $(this).closest("form").submit();
    });

    $(document).on("click", "a.async", function (e) {
        e.preventDefault();
        $(this).blur();

        var wait = $(this).closest(".wait").length > 0;
        if (wait)
            $("body").addClass("wait");

        $.ajax({
            url: $(this).attr("href"),
            dataType: "json",
            type: $(this).is(".post") ? "POST" : "GET"
        }).done(function (res) {
            handle_json(res);
        }).fail(handle_ajax_failure);
    });
});


/* assets/home.js */ jQuery(function ($) {
    var splashes = $(".splashes ul");
    if (splashes.length > 0) {
        splashes.bxSlider({
            auto: true,
            pager: false,
            controls: true,
            autoControls: false,
            speed: 200,
            pause: 5500
        });
    }
});


/* lib/local/comments.js */ jQuery(function ($) {
    var dt = $("#disqus_thread");
    if (dt.length) {
        window.disqus_config = function () {
            this.page.url = dt.attr("data-url");
            this.page.identifier = dt.attr("data-id");
        };

        var d = document,
            s = d.createElement('script');

        s.src = 'https://seb-museum.disqus.com/embed.js';
        s.setAttribute('data-timestamp', +new Date());
        (d.head || d.body).appendChild(s);
    }
});


/* lib/local/admin.js */ jQuery(function ($) {
    $(document).on("click", "button.copy", function (e) {
        var data = $(this).attr("data-clipboard");
        if (data) {
            e.preventDefault();

            try {
                var t = document.createElement("textarea");
                t.style.position = "fixed";
                t.style.top = 0;
                t.style.left = 0;
                t.style.width = "2em";
                t.style.height = "2em";
                t.style.padding = 0;
                t.style.border = "none";
                t.style.outline = "none";
                t.style.boxShadow = "none";
                t.style.background = "transparent";
                t.value = data;
                document.body.appendChild(t);
                t.select();
                document.execCommand("copy");
                document.body.removeChild(t);
            } catch (e) {
                alert(e);
            }
        }
    });

    $(document).on("click", "a[data-filter]", function (e) {
        e.preventDefault();

        var f = $(this).attr("data-filter");
        $(".admphotos .item").hide();
        $(".admphotos .item." + f).show();

        $(this).blur();
        $(this).closest(".nav").find(".active").removeClass("active");
        $(this).closest("li").addClass("active");
    });

    $(document).on("click", ".admphotos a.delete", function (e) {
        e.preventDefault();

        var item = $(this).closest(".item");

        $.ajax({
            url: $(this).attr("href"),
            type: "POST",
            dataType: "json"
        }).done(function (res) {
            res = $.extend({
                status: "draft",
            }, res);

            if (res.status == "draft") {
                item.hide();
                item.removeClass("published").addClass("draft");
            } else if (res.status == "deleted") {
                item.remove();
            }

            update_photo_counts();
        }).fail(handle_ajax_failure);
    });

    var update_photo_counts = function () {
        var cpub = $(".admphotos .published").length;
        var chid = $(".admphotos .draft").length;
        if (chid)
            $("#chid").text(chid).show();
        else
            $("#chid").hide();
    };
    update_photo_counts();

    $(document).on("keydown", function (e) {
        // ctrlKey shiftKey altKey
        if (e.ctrlKey && e.altKey && e.keyCode == 69) {  // "e"
            if ("jsdata" in window && "edit_link" in window.jsdata) {
                window.location.href = window.jsdata["edit_link"];
            } else {
                alert("Не удалось найти ссылку на страницу редактирования.");
            }
        }
    });
});


/* lib/local/maps.js */ jQuery(function ($) {
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


/* lib/local/gallery.js */ jQuery(function ($) {
    $(document).on("change", "input[type=file].gallery", function (e) {
        var $this = $(this);

        var files = $this.get(0).files;
        if (files.length == 0)
            return;

        var fd = new FormData();

        for (var idx in files) {
            fd.append("files[]", files[idx]);
        }

        console && console.log("uploading {0} files to gallery.".format(files.length));

        $.ajax({
            url: "/admin/upload",
            type: "POST",
            data: fd,
            processData: false,
            contentType: false,
            dataType: "json"
        }).done(function (res) {
            res = $.extend({
                ids: [],
                message: null
            }, res);

            // Save to the control.
            var hctl = $this.closest(".form-group").find("input[name=gallery]");
            var value = hctl.val();
            if (value)
                value += ",";
            hctl.val(value + res.ids.join(","));

            // Render gallery preview.
            $.ajax({
                url: "/admin/gallery/preview",
                data: {files: hctl.val()},
                type: "GET",
                dataType: "json"
            }).done(function (res) {
                var fg = $this.closest(".gallery-group");
                fg.find("a.thumbnail").remove();
                fg.prepend(res.html);
            });
        }).fail(function (res) {
            alert("FAILURE");
        });
    });

    /**
     * Удаление элемента из галереи.
     *
     * Удаляет текущий объект, затем пересобирает список идентификаторов
     * и засовывает в ближайший input[name=gallery].
     **/
    $(document).on("click", ".thumbnail .delete", function (e) {
        e.preventDefault();

        var fg = $(this).closest(".form-group");

        $(this).closest(".thumbnail").remove();

        var ids = [];
        fg.find("a.thumbnail").each(function () {
            var id = $(this).attr("data-id");
            if (id)
                ids.push(id);
        });

        var ids = ids.join(",");
        fg.find("input[name=gallery]").val(ids);
    });
});


/* lib/local/photos.js */ window.vk_photo_ready = function (res) {
    res = $.extend({
        link: null
    }, res);

    var l = $("#vk_link");

    l.attr("href", res.link);
    l.show();
};


jQuery(function ($) {
    var parse_selection = function (sel, scale) {
        if (sel == "")
            return null;

        sel = sel.split(",");

        var x = Math.round(sel[0] / scale),
            y = Math.round(sel[1] / scale),
            w = Math.round(sel[2] / scale),
            h = Math.round(sel[3] / scale);

        return [x, y, x + w, y + h];
    };

    var init = function (img) {
        var rw = parseInt(img.attr("data-width")),
            rh = parseInt(img.attr("data-height")),
            iw = img.width(),
            scale = rw / iw;

        var api,
            storage = img.attr("data-storage");

        if (!storage) {
            alert("img.data-storage not set");
            return;
        } else {
            var ctl = $(storage);
            if (ctl.length == 0) {
                alert(storage + " not found");
                return;
            } else {
                storage = ctl;
            }
        }

        img.Jcrop({
            setSelect: parse_selection(storage.val(), scale),

            onSelect: function (c) {
                var str = Math.round(c.x * scale) + "," + Math.round(c.y * scale) + "," + Math.round(c.w * scale) + "," + Math.round(c.h * scale);
                storage.val(str);
                console.log("selection update: " + str);
            },

            onRelease: function (c) {
                storage.val("");
            }
        }, function () { api = this; });
    };

    $("img.cropme").on("load", function () {
        init($(this));
    });

    $(document).on("click", ".variants a", function (e) {
        e.preventDefault();

        var src = $(this).attr("href"),
            i = $(this).closest(".bigphoto").find(".img");
        i.css("background-image", "url(" + src + ")");
    });

    $(document).on("keydown", function (e) {
        if (!e.ctrlKey && !e.altKey && !e.shiftKey) {
            var next;

            switch (e.keyCode) {
            case 37:  // left
                next = $("a.nav.left").attr("href");
                break;
            case 39:  // right
                next = $("a.nav.right").attr("href");
                break;
            }

            if (next)
                window.location.href = next;
        }
    });
});


/* lib/local/hotkeys.js */ jQuery(function ($) {
    $(document).on("keydown", function (e) {
        if (e.ctrlKey && e.keyCode == 69) {  // "e"
            var links = $("link[rel=edit]");
            if (links.length == 1) {
                window.location.href = links.eq(0).attr("href");
            }
        }

        if (e.ctrlKey && e.key == "D") {  // "d"
            e.preventDefault();
            var link = window.location.href;
            if (window.location.search)
                link += "&debug=tpl";
            else
                link += "?debug=tpl";
            window.location.href = link;
        }
    });

    $(document).on("keydown", "form", function (e) {
        if (e.ctrlKey && e.keyCode == 13) {
            $(this).find(".btn-primary").eq(0).click();
        }
    });

    $(document).on("keydown", "textarea.markdown", function (e) {
        // External markdown links.
        if (e.altKey && (e.key == "[" || e.key == "х")) {
            var v = this.value,
                s = this.selectionStart,
                e = this.selectionEnd;
            var text = v.substring(0, s) + "[" + v.substring(s, e) + "]()" + v.substring(e);
            this.value = text;
            this.selectionStart = e + 3;
            this.selectionEnd = e + 3;
        }

        // Bold.
        if (e.ctrlKey && e.keyCode == 66) {
            var v = this.value,
                s = this.selectionStart,
                e = this.selectionEnd;

            var src = v.substring(s, e);
            if (src == "") {
                console.log("empty selection");
            }

            else if (v[s] == "*") {
                console.log("already bold");
            }

            else if (s > 0 && v[s-1] == "*") {
                console.log("already bold");
            }

            else {
                v = v.substring(0, s) + "**" + v.substring(s, e) + "**" + v.substring(e);
                this.value = v;
                this.selectionStart = s;
                this.selectionEnd = e + 4;
                console.log("making bold");
            }

            return false;
        }

        // Make itemized list from selected lines
        if (e.altKey && e.key == "-") {
            var v = this.value,
                s = this.selectionStart,
                e = this.selectionEnd;

            var src = v.substring(s, e);

            var lines = src.match(/[^\r\n]+/g);
            for (var i in lines) {
                var line = lines[i];
                line = "- " + line.replace(/^\s+|\s+$/, "");
                while (line.substring(0, 4) == "- - ")
                    line = line.substring(2);
                lines[i] = line;
            }

            lines = lines.join("\n") + "\n";
            var dst = v.substring(0, s) + lines + v.substring(e);

            this.value = dst;
            this.selectionStart = s + lines.length;
            this.selectionEnd = s + lines.length;
        }
    });
});


/* assets/eval.js */ jQuery(function ($) {
    var conden = function (srcid, dstid) {
        var checked = $("input[name='answer[" + srcid + "]']:checked").val() == "yes";
        var ctl = $("input[name='answer[" + dstid + "]']");
        ctl.prop("disabled", !checked);
    };

    var setup = function (sel, b) {
        conden("1", "2");
        conden("3", "4");
        conden("6", "7");
        conden("6", "8");
    };

    $(document).on("change", "form.eval input", function (e) {
        setup();
    });

    setup();
});
