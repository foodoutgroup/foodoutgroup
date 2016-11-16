(function (lib, img, cjs, ss) {

var p; // shortcut to reference prototypes
lib.webFontTxtInst = {};
var loadedTypekitCount = 0;
var loadedGoogleCount = 0;
var gFontsUpdateCacheList = [];
var tFontsUpdateCacheList = [];

// library properties:
lib.properties = {
    width: 900,
    height: 300,
    fps: 24,
    color: "#FFFFFF",
    opacity: 1.00,
    webfonts: {},
    manifest: [
        {src:"https://foodout.lt/fobirthday/900x300_FO_gimtadienis_atlas_.png", id:"900x300_FO_gimtadienis_atlas_"}
    ]
};



lib.ssMetadata = [
        {name:"900x300_FO_gimtadienis_atlas_", frames: [[0,302,900,300],[0,906,900,300],[0,604,900,300],[902,302,900,300],[0,0,900,300],[902,0,900,300],[902,604,900,300]]}
];



lib.updateListCache = function (cacheList) {
    for(var i = 0; i < cacheList.length; i++) {
        if(cacheList[i].cacheCanvas)
            cacheList[i].updateCache();
    }
};

lib.addElementsToCache = function (textInst, cacheList) {
    var cur = textInst;
    while(cur != exportRoot) {
        if(cacheList.indexOf(cur) != -1)
            break;
        cur = cur.parent;
    }
    if(cur != exportRoot) { //we have found an element in the list
        var cur2 = textInst;
        var index = cacheList.indexOf(cur);
        while(cur2 != cur) { //insert all it's children just before it
            cacheList.splice(index, 0, cur2);
            cur2 = cur2.parent;
            index++;
        }
    }
    else {  //append element and it's parents in the array
        cur = textInst;
        while(cur != exportRoot) {
            cacheList.push(cur);
            cur = cur.parent;
        }
    }
};

lib.gfontAvailable = function(family, totalGoogleCount) {
    lib.properties.webfonts[family] = true;
    var txtInst = lib.webFontTxtInst && lib.webFontTxtInst[family] || [];
    for(var f = 0; f < txtInst.length; ++f)
        lib.addElementsToCache(txtInst[f], gFontsUpdateCacheList);

    loadedGoogleCount++;
    if(loadedGoogleCount == totalGoogleCount) {
        lib.updateListCache(gFontsUpdateCacheList);
    }
};

lib.tfontAvailable = function(family, totalTypekitCount) {
    lib.properties.webfonts[family] = true;
    var txtInst = lib.webFontTxtInst && lib.webFontTxtInst[family] || [];
    for(var f = 0; f < txtInst.length; ++f)
        lib.addElementsToCache(txtInst[f], tFontsUpdateCacheList);

    loadedTypekitCount++;
    if(loadedTypekitCount == totalTypekitCount) {
        lib.updateListCache(tFontsUpdateCacheList);
    }
};
// symbols:



(lib.back = function() {
    this.spriteSheet = ss["900x300_FO_gimtadienis_atlas_"];
    this.gotoAndStop(0);
}).prototype = p = new cjs.Sprite();



(lib.gimtadienis = function() {
    this.spriteSheet = ss["900x300_FO_gimtadienis_atlas_"];
    this.gotoAndStop(1);
}).prototype = p = new cjs.Sprite();



(lib.metai = function() {
    this.spriteSheet = ss["900x300_FO_gimtadienis_atlas_"];
    this.gotoAndStop(2);
}).prototype = p = new cjs.Sprite();



(lib.svesk = function() {
    this.spriteSheet = ss["900x300_FO_gimtadienis_atlas_"];
    this.gotoAndStop(3);
}).prototype = p = new cjs.Sprite();



(lib.t01 = function() {
    this.spriteSheet = ss["900x300_FO_gimtadienis_atlas_"];
    this.gotoAndStop(4);
}).prototype = p = new cjs.Sprite();



(lib.t02 = function() {
    this.spriteSheet = ss["900x300_FO_gimtadienis_atlas_"];
    this.gotoAndStop(5);
}).prototype = p = new cjs.Sprite();



(lib.t03 = function() {
    this.spriteSheet = ss["900x300_FO_gimtadienis_atlas_"];
    this.gotoAndStop(6);
}).prototype = p = new cjs.Sprite();



(lib.Symbol6 = function(mode,startPosition,loop) {
    this.initialize(mode,startPosition,loop,{});

    // Layer 1
    this.instance = new lib.t03();
    this.instance.parent = this;

    this.timeline.addTween(cjs.Tween.get(this.instance).wait(1));

}).prototype = p = new cjs.MovieClip();
p.nominalBounds = new cjs.Rectangle(0,0,900,300);


(lib.Symbol5 = function(mode,startPosition,loop) {
    this.initialize(mode,startPosition,loop,{});

    // Layer 1
    this.instance = new lib.t02();
    this.instance.parent = this;

    this.timeline.addTween(cjs.Tween.get(this.instance).wait(1));

}).prototype = p = new cjs.MovieClip();
p.nominalBounds = new cjs.Rectangle(0,0,900,300);


(lib.Symbol4 = function(mode,startPosition,loop) {
    this.initialize(mode,startPosition,loop,{});

    // Layer 1
    this.instance = new lib.t01();
    this.instance.parent = this;

    this.timeline.addTween(cjs.Tween.get(this.instance).wait(1));

}).prototype = p = new cjs.MovieClip();
p.nominalBounds = new cjs.Rectangle(0,0,900,300);


(lib.Symbol3 = function(mode,startPosition,loop) {
    this.initialize(mode,startPosition,loop,{});

    // Layer 1
    this.instance = new lib.svesk();
    this.instance.parent = this;

    this.timeline.addTween(cjs.Tween.get(this.instance).wait(1));

}).prototype = p = new cjs.MovieClip();
p.nominalBounds = new cjs.Rectangle(0,0,900,300);


(lib.Symbol2 = function(mode,startPosition,loop) {
    this.initialize(mode,startPosition,loop,{});

    // Layer 1
    this.instance = new lib.metai();
    this.instance.parent = this;

    this.timeline.addTween(cjs.Tween.get(this.instance).wait(1));

}).prototype = p = new cjs.MovieClip();
p.nominalBounds = new cjs.Rectangle(0,0,900,300);


(lib.Symbol1 = function(mode,startPosition,loop) {
    this.initialize(mode,startPosition,loop,{});

    // Layer 1
    this.instance = new lib.gimtadienis();
    this.instance.parent = this;

    this.timeline.addTween(cjs.Tween.get(this.instance).wait(1));

}).prototype = p = new cjs.MovieClip();
p.nominalBounds = new cjs.Rectangle(0,0,900,300);


// stage content:
(lib._900x300_FOgimtadienis = function(mode,startPosition,loop) {
    this.initialize(mode,startPosition,loop,{});

    // t03
    this.instance = new lib.Symbol6();
    this.instance.parent = this;
    this.instance.setTransform(450,112,1,1,0,0,0,450,150);
    this.instance.alpha = 0;
    this.instance._off = true;

    this.timeline.addTween(cjs.Tween.get(this.instance).wait(49).to({_off:false},0).wait(1).to({y:114.6,alpha:0.067},0).wait(1).to({y:117.1,alpha:0.133},0).wait(1).to({y:119.6,alpha:0.2},0).wait(1).to({y:122.2,alpha:0.267},0).wait(1).to({y:124.7,alpha:0.333},0).wait(1).to({y:127.2,alpha:0.4},0).wait(1).to({y:129.8,alpha:0.467},0).wait(1).to({y:132.3,alpha:0.533},0).wait(1).to({y:134.8,alpha:0.6},0).wait(1).to({y:137.4,alpha:0.667},0).wait(1).to({y:139.9,alpha:0.733},0).wait(1).to({y:142.4,alpha:0.8},0).wait(1).to({y:145,alpha:0.867},0).wait(1).to({y:147.5,alpha:0.933},0).wait(1).to({y:150,alpha:1},0).wait(58).to({y:153.8,alpha:0.9},0).wait(1).to({y:157.6,alpha:0.8},0).wait(1).to({y:161.4,alpha:0.7},0).wait(1).to({y:165.2,alpha:0.6},0).wait(1).to({y:169,alpha:0.5},0).wait(1).to({y:172.8,alpha:0.4},0).wait(1).to({y:176.6,alpha:0.3},0).wait(1).to({y:180.4,alpha:0.2},0).wait(1).to({y:184.2,alpha:0.1},0).wait(1).to({y:188,alpha:0},0).to({_off:true},1).wait(35));

    // t02
    this.instance_1 = new lib.Symbol5();
    this.instance_1.parent = this;
    this.instance_1.setTransform(450,112,1,1,0,0,0,450,150);
    this.instance_1.alpha = 0;
    this.instance_1._off = true;

    this.timeline.addTween(cjs.Tween.get(this.instance_1).wait(39).to({_off:false},0).wait(1).to({y:113.4,alpha:0.034},0).wait(1).to({y:114.8,alpha:0.068},0).wait(1).to({y:116.2,alpha:0.103},0).wait(1).to({y:117.7,alpha:0.138},0).wait(1).to({y:119.2,alpha:0.174},0).wait(1).to({y:120.6,alpha:0.21},0).wait(1).to({y:122.2,alpha:0.248},0).wait(1).to({y:123.7,alpha:0.285},0).wait(1).to({y:125.3,alpha:0.323},0).wait(1).to({y:126.8,alpha:0.362},0).wait(1).to({y:128.5,alpha:0.402},0).wait(1).to({y:130.1,alpha:0.442},0).wait(1).to({y:131.8,alpha:0.482},0).wait(1).to({y:133.4,alpha:0.523},0).wait(1).to({y:135.1,alpha:0.565},0).wait(1).to({y:136.9,alpha:0.608},0).wait(1).to({y:138.6,alpha:0.65},0).wait(1).to({y:140.4,alpha:0.694},0).wait(1).to({y:142.2,alpha:0.738},0).wait(1).to({y:144,alpha:0.783},0).wait(1).to({y:145.9,alpha:0.828},0).wait(1).to({y:147.7,alpha:0.874},0).wait(1).to({y:149.6,alpha:0.92},0).wait(1).to({y:151.5,alpha:0.967},0).wait(1).to({y:152.7,alpha:1},0).wait(1).to({y:152.3},0).wait(1).to({y:151.9},0).wait(1).to({y:151.5},0).wait(1).to({y:151.1},0).wait(1).to({y:150.6},0).wait(1).to({y:150.2},0).wait(1).to({y:149.8},0).wait(1).to({y:149.3},0).wait(1).to({y:148.9},0).wait(1).to({y:148.4},0).wait(1).to({y:148},0).wait(1).to({y:147.5},0).wait(1).to({y:147.4},0).wait(1).to({y:147.9},0).wait(1).to({y:148.5},0).wait(1).to({y:149},0).wait(1).to({y:149.6},0).wait(1).to({y:150.1},0).wait(1).to({y:150.7},0).wait(1).to({y:151.2},0).wait(1).to({y:151.8},0).wait(1).to({y:152.4},0).wait(1).to({y:153},0).wait(1).to({y:153.6},0).wait(1).to({y:153},0).wait(1).to({y:152.4},0).wait(1).to({y:151.7},0).wait(1).to({y:151.1},0).wait(1).to({y:150.4},0).wait(1).to({y:149.8},0).wait(1).to({y:149.2},0).wait(1).to({y:148.5},0).wait(1).to({y:147.8},0).wait(1).to({y:147.1},0).wait(1).to({y:147.3},0).wait(1).to({y:148},0).wait(1).to({y:148.7},0).wait(1).to({y:149.4},0).wait(1).to({y:150},0).wait(1).to({y:150.7},0).wait(1).to({y:151.5},0).wait(1).to({y:152.2},0).wait(1).to({y:152.9},0).wait(1).to({y:153.4},0).wait(1).to({y:152.7},0).wait(1).to({y:152},0).wait(1).to({y:151.3},0).wait(1).to({y:150.6},0).wait(1).to({y:150},0).wait(1).to({y:149.2},0).wait(1).to({y:148.5},0).wait(1).to({y:147.8},0).wait(1).to({y:147.5},0).wait(1).to({y:147.8},0).wait(1).to({y:148.1},0).wait(1).to({y:148.4},0).wait(1).to({y:148.8},0).wait(1).to({y:149.1},0).wait(1).to({y:149.4},0).wait(1).to({y:149.8},0).wait(1).to({y:150.6,alpha:0.984},0).wait(1).to({y:155.4,alpha:0.859},0).wait(1).to({y:160.1,alpha:0.733},0).wait(1).to({y:165,alpha:0.606},0).wait(1).to({y:169.8,alpha:0.478},0).wait(1).to({y:174.7,alpha:0.35},0).wait(1).to({y:179.6,alpha:0.22},0).wait(1).to({y:184.6,alpha:0.09},0).wait(1).to({y:188,alpha:0},0).wait(21).to({_off:true},1).wait(12));

    // t01
    this.instance_2 = new lib.Symbol4();
    this.instance_2.parent = this;
    this.instance_2.setTransform(450,112,1,1,0,0,0,450,150);
    this.instance_2.alpha = 0;
    this.instance_2._off = true;

    this.timeline.addTween(cjs.Tween.get(this.instance_2).wait(29).to({_off:false},0).wait(1).to({y:114.6,alpha:0.067},0).wait(1).to({y:117.1,alpha:0.133},0).wait(1).to({y:119.6,alpha:0.2},0).wait(1).to({y:122.2,alpha:0.267},0).wait(1).to({y:124.7,alpha:0.333},0).wait(1).to({y:127.2,alpha:0.4},0).wait(1).to({y:129.8,alpha:0.467},0).wait(1).to({y:132.3,alpha:0.533},0).wait(1).to({y:134.8,alpha:0.6},0).wait(1).to({y:137.4,alpha:0.667},0).wait(1).to({y:139.9,alpha:0.733},0).wait(1).to({y:142.4,alpha:0.8},0).wait(1).to({y:145,alpha:0.867},0).wait(1).to({y:147.5,alpha:0.933},0).wait(1).to({y:150,alpha:1},0).wait(88).to({y:153.8,alpha:0.9},0).wait(1).to({y:157.6,alpha:0.8},0).wait(1).to({y:161.4,alpha:0.7},0).wait(1).to({y:165.2,alpha:0.6},0).wait(1).to({y:169,alpha:0.5},0).wait(1).to({y:172.8,alpha:0.4},0).wait(1).to({y:176.6,alpha:0.3},0).wait(1).to({y:180.4,alpha:0.2},0).wait(1).to({y:184.2,alpha:0.1},0).wait(1).to({y:188,alpha:0},0).wait(26));

    // svesk
    this.instance_3 = new lib.Symbol3();
    this.instance_3.parent = this;
    this.instance_3.setTransform(450,112,1,1,0,0,0,450,150);
    this.instance_3.alpha = 0;
    this.instance_3._off = true;

    this.timeline.addTween(cjs.Tween.get(this.instance_3).wait(19).to({_off:false},0).wait(1).to({y:114.6,alpha:0.067},0).wait(1).to({y:117.1,alpha:0.133},0).wait(1).to({y:119.6,alpha:0.2},0).wait(1).to({y:122.2,alpha:0.267},0).wait(1).to({y:124.7,alpha:0.333},0).wait(1).to({y:127.2,alpha:0.4},0).wait(1).to({y:129.8,alpha:0.467},0).wait(1).to({y:132.3,alpha:0.533},0).wait(1).to({y:134.8,alpha:0.6},0).wait(1).to({y:137.4,alpha:0.667},0).wait(1).to({y:139.9,alpha:0.733},0).wait(1).to({y:142.4,alpha:0.8},0).wait(1).to({y:145,alpha:0.867},0).wait(1).to({y:147.5,alpha:0.933},0).wait(1).to({y:150,alpha:1},0).wait(103).to({y:153.8,alpha:0.9},0).wait(1).to({y:157.6,alpha:0.8},0).wait(1).to({y:161.4,alpha:0.7},0).wait(1).to({y:165.2,alpha:0.6},0).wait(1).to({y:169,alpha:0.5},0).wait(1).to({y:172.8,alpha:0.4},0).wait(1).to({y:176.6,alpha:0.3},0).wait(1).to({y:180.4,alpha:0.2},0).wait(1).to({y:184.2,alpha:0.1},0).wait(1).to({y:188,alpha:0},0).wait(21));

    // metukai
    this.instance_4 = new lib.Symbol2();
    this.instance_4.parent = this;
    this.instance_4.setTransform(450,150,1,1,0,0,0,450,150);
    this.instance_4.alpha = 0;
    this.instance_4._off = true;

    this.timeline.addTween(cjs.Tween.get(this.instance_4).wait(9).to({_off:false},0).wait(1).to({alpha:0.001},0).wait(1).to({alpha:0.003},0).wait(1).to({alpha:0.006},0).wait(1).to({alpha:0.01},0).wait(1).to({alpha:0.016},0).wait(1).to({alpha:0.023},0).wait(1).to({alpha:0.031},0).wait(1).to({alpha:0.041},0).wait(1).to({alpha:0.052},0).wait(1).to({alpha:0.064},0).wait(1).to({alpha:0.077},0).wait(1).to({alpha:0.092},0).wait(1).to({alpha:0.108},0).wait(1).to({alpha:0.125},0).wait(1).to({alpha:0.143},0).wait(1).to({alpha:0.163},0).wait(1).to({alpha:0.184},0).wait(1).to({alpha:0.206},0).wait(1).to({alpha:0.23},0).wait(1).to({alpha:0.255},0).wait(1).to({alpha:0.281},0).wait(1).to({alpha:0.308},0).wait(1).to({alpha:0.337},0).wait(1).to({alpha:0.367},0).wait(1).to({alpha:0.398},0).wait(1).to({alpha:0.431},0).wait(1).to({alpha:0.464},0).wait(1).to({alpha:0.499},0).wait(1).to({alpha:0.536},0).wait(1).to({alpha:0.573},0).wait(1).to({alpha:0.612},0).wait(1).to({alpha:0.652},0).wait(1).to({alpha:0.694},0).wait(1).to({alpha:0.736},0).wait(1).to({alpha:0.78},0).wait(1).to({alpha:0.825},0).wait(1).to({alpha:0.872},0).wait(1).to({alpha:0.92},0).wait(1).to({alpha:0.969},0).wait(1).to({alpha:1},0).wait(104).to({alpha:0.992},0).wait(1).to({alpha:0.808},0).wait(1).to({alpha:0.623},0).wait(1).to({alpha:0.436},0).wait(1).to({alpha:0.249},0).wait(1).to({alpha:0.059},0).wait(1).to({alpha:0},0).wait(8));

    // gimtadienis
    this.instance_5 = new lib.Symbol1();
    this.instance_5.parent = this;
    this.instance_5.setTransform(450,150,1,1,0,0,0,450,150);
    this.instance_5.alpha = 0;

    this.timeline.addTween(cjs.Tween.get(this.instance_5).wait(1).to({alpha:0.001},0).wait(1).to({alpha:0.003},0).wait(1).to({alpha:0.006},0).wait(1).to({alpha:0.011},0).wait(1).to({alpha:0.017},0).wait(1).to({alpha:0.024},0).wait(1).to({alpha:0.033},0).wait(1).to({alpha:0.043},0).wait(1).to({alpha:0.054},0).wait(1).to({alpha:0.067},0).wait(1).to({alpha:0.081},0).wait(1).to({alpha:0.096},0).wait(1).to({alpha:0.113},0).wait(1).to({alpha:0.131},0).wait(1).to({alpha:0.151},0).wait(1).to({alpha:0.171},0).wait(1).to({alpha:0.193},0).wait(1).to({alpha:0.217},0).wait(1).to({alpha:0.242},0).wait(1).to({alpha:0.268},0).wait(1).to({alpha:0.295},0).wait(1).to({alpha:0.324},0).wait(1).to({alpha:0.354},0).wait(1).to({alpha:0.385},0).wait(1).to({alpha:0.418},0).wait(1).to({alpha:0.452},0).wait(1).to({alpha:0.488},0).wait(1).to({alpha:0.525},0).wait(1).to({alpha:0.563},0).wait(1).to({alpha:0.602},0).wait(1).to({alpha:0.643},0).wait(1).to({alpha:0.685},0).wait(1).to({alpha:0.729},0).wait(1).to({alpha:0.774},0).wait(1).to({alpha:0.82},0).wait(1).to({alpha:0.867},0).wait(1).to({alpha:0.916},0).wait(1).to({alpha:0.966},0).wait(1).to({alpha:1},0).wait(114).to({alpha:0.998},0).wait(1).to({alpha:0.813},0).wait(1).to({alpha:0.627},0).wait(1).to({alpha:0.44},0).wait(1).to({alpha:0.251},0).wait(1).to({alpha:0.062},0).wait(1).to({alpha:0},0).wait(8));

    // back
    this.instance_6 = new lib.back();
    this.instance_6.parent = this;

    this.timeline.addTween(cjs.Tween.get(this.instance_6).wait(167));

}).prototype = p = new cjs.MovieClip();
p.nominalBounds = new cjs.Rectangle(450,150,900,300);

})(lib = lib||{}, images = images||{}, createjs = createjs||{}, ss = ss||{});
var lib, images, createjs, ss;
