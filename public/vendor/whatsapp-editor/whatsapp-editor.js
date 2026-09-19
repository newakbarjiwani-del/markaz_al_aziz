var ji = Object.defineProperty;
var Gi = (r, e, t) => e in r ? ji(r, e, { enumerable: !0, configurable: !0, writable: !0, value: t }) : r[e] = t;
var re = (r, e, t) => (Gi(r, typeof e != "symbol" ? e + "" : e, t), t);
function T(r) {
  this.content = r;
}
T.prototype = {
  constructor: T,
  find: function(r) {
    for (var e = 0; e < this.content.length; e += 2)
      if (this.content[e] === r)
        return e;
    return -1;
  },
  get: function(r) {
    var e = this.find(r);
    return e == -1 ? void 0 : this.content[e + 1];
  },
  update: function(r, e, t) {
    var n = t && t != r ? this.remove(t) : this, i = n.find(r), s = n.content.slice();
    return i == -1 ? s.push(t || r, e) : (s[i + 1] = e, t && (s[i] = t)), new T(s);
  },
  remove: function(r) {
    var e = this.find(r);
    if (e == -1)
      return this;
    var t = this.content.slice();
    return t.splice(e, 2), new T(t);
  },
  addToStart: function(r, e) {
    return new T([r, e].concat(this.remove(r).content));
  },
  addToEnd: function(r, e) {
    var t = this.remove(r).content.slice();
    return t.push(r, e), new T(t);
  },
  addBefore: function(r, e, t) {
    var n = this.remove(e), i = n.content.slice(), s = n.find(r);
    return i.splice(s == -1 ? i.length : s, 0, e, t), new T(i);
  },
  forEach: function(r) {
    for (var e = 0; e < this.content.length; e += 2)
      r(this.content[e], this.content[e + 1]);
  },
  prepend: function(r) {
    return r = T.from(r), r.size ? new T(r.content.concat(this.subtract(r).content)) : this;
  },
  append: function(r) {
    return r = T.from(r), r.size ? new T(this.subtract(r).content.concat(r.content)) : this;
  },
  subtract: function(r) {
    var e = this;
    r = T.from(r);
    for (var t = 0; t < r.content.length; t += 2)
      e = e.remove(r.content[t]);
    return e;
  },
  toObject: function() {
    var r = {};
    return this.forEach(function(e, t) {
      r[e] = t;
    }), r;
  },
  get size() {
    return this.content.length >> 1;
  }
};
T.from = function(r) {
  if (r instanceof T)
    return r;
  var e = [];
  if (r)
    for (var t in r)
      e.push(t, r[t]);
  return new T(e);
};
function Rr(r, e, t) {
  for (let n = 0; ; n++) {
    if (n == r.childCount || n == e.childCount)
      return r.childCount == e.childCount ? null : t;
    let i = r.child(n), s = e.child(n);
    if (i == s) {
      t += i.nodeSize;
      continue;
    }
    if (!i.sameMarkup(s))
      return t;
    if (i.isText && i.text != s.text) {
      for (let o = 0; i.text[o] == s.text[o]; o++)
        t++;
      return t;
    }
    if (i.content.size || s.content.size) {
      let o = Rr(i.content, s.content, t + 1);
      if (o != null)
        return o;
    }
    t += i.nodeSize;
  }
}
function Pr(r, e, t, n) {
  for (let i = r.childCount, s = e.childCount; ; ) {
    if (i == 0 || s == 0)
      return i == s ? null : { a: t, b: n };
    let o = r.child(--i), l = e.child(--s), a = o.nodeSize;
    if (o == l) {
      t -= a, n -= a;
      continue;
    }
    if (!o.sameMarkup(l))
      return { a: t, b: n };
    if (o.isText && o.text != l.text) {
      let h = 0, c = Math.min(o.text.length, l.text.length);
      for (; h < c && o.text[o.text.length - h - 1] == l.text[l.text.length - h - 1]; )
        h++, t--, n--;
      return { a: t, b: n };
    }
    if (o.content.size || l.content.size) {
      let h = Pr(o.content, l.content, t - 1, n - 1);
      if (h)
        return h;
    }
    t -= a, n -= a;
  }
}
class g {
  constructor(e, t) {
    if (this.content = e, this.size = t || 0, t == null)
      for (let n = 0; n < e.length; n++)
        this.size += e[n].nodeSize;
  }
  nodesBetween(e, t, n, i = 0, s) {
    for (let o = 0, l = 0; l < t; o++) {
      let a = this.content[o], h = l + a.nodeSize;
      if (h > e && n(a, i + l, s || null, o) !== !1 && a.content.size) {
        let c = l + 1;
        a.nodesBetween(Math.max(0, e - c), Math.min(a.content.size, t - c), n, i + c);
      }
      l = h;
    }
  }
  descendants(e) {
    this.nodesBetween(0, this.size, e);
  }
  textBetween(e, t, n, i) {
    let s = "", o = !0;
    return this.nodesBetween(e, t, (l, a) => {
      l.isText ? (s += l.text.slice(Math.max(e, a) - a, t - a), o = !n) : l.isLeaf ? (i ? s += typeof i == "function" ? i(l) : i : l.type.spec.leafText && (s += l.type.spec.leafText(l)), o = !n) : !o && l.isBlock && (s += n, o = !0);
    }, 0), s;
  }
  append(e) {
    if (!e.size)
      return this;
    if (!this.size)
      return e;
    let t = this.lastChild, n = e.firstChild, i = this.content.slice(), s = 0;
    for (t.isText && t.sameMarkup(n) && (i[i.length - 1] = t.withText(t.text + n.text), s = 1); s < e.content.length; s++)
      i.push(e.content[s]);
    return new g(i, this.size + e.size);
  }
  cut(e, t = this.size) {
    if (e == 0 && t == this.size)
      return this;
    let n = [], i = 0;
    if (t > e)
      for (let s = 0, o = 0; o < t; s++) {
        let l = this.content[s], a = o + l.nodeSize;
        a > e && ((o < e || a > t) && (l.isText ? l = l.cut(Math.max(0, e - o), Math.min(l.text.length, t - o)) : l = l.cut(Math.max(0, e - o - 1), Math.min(l.content.size, t - o - 1))), n.push(l), i += l.nodeSize), o = a;
      }
    return new g(n, i);
  }
  cutByIndex(e, t) {
    return e == t ? g.empty : e == 0 && t == this.content.length ? this : new g(this.content.slice(e, t));
  }
  replaceChild(e, t) {
    let n = this.content[e];
    if (n == t)
      return this;
    let i = this.content.slice(), s = this.size + t.nodeSize - n.nodeSize;
    return i[e] = t, new g(i, s);
  }
  addToStart(e) {
    return new g([e].concat(this.content), this.size + e.nodeSize);
  }
  addToEnd(e) {
    return new g(this.content.concat(e), this.size + e.nodeSize);
  }
  eq(e) {
    if (this.content.length != e.content.length)
      return !1;
    for (let t = 0; t < this.content.length; t++)
      if (!this.content[t].eq(e.content[t]))
        return !1;
    return !0;
  }
  get firstChild() {
    return this.content.length ? this.content[0] : null;
  }
  get lastChild() {
    return this.content.length ? this.content[this.content.length - 1] : null;
  }
  get childCount() {
    return this.content.length;
  }
  child(e) {
    let t = this.content[e];
    if (!t)
      throw new RangeError("Index " + e + " out of range for " + this);
    return t;
  }
  maybeChild(e) {
    return this.content[e] || null;
  }
  forEach(e) {
    for (let t = 0, n = 0; t < this.content.length; t++) {
      let i = this.content[t];
      e(i, n, t), n += i.nodeSize;
    }
  }
  findDiffStart(e, t = 0) {
    return Rr(this, e, t);
  }
  findDiffEnd(e, t = this.size, n = e.size) {
    return Pr(this, e, t, n);
  }
  findIndex(e, t = -1) {
    if (e == 0)
      return ht(0, e);
    if (e == this.size)
      return ht(this.content.length, e);
    if (e > this.size || e < 0)
      throw new RangeError(`Position ${e} outside of fragment (${this})`);
    for (let n = 0, i = 0; ; n++) {
      let s = this.child(n), o = i + s.nodeSize;
      if (o >= e)
        return o == e || t > 0 ? ht(n + 1, o) : ht(n, i);
      i = o;
    }
  }
  toString() {
    return "<" + this.toStringInner() + ">";
  }
  toStringInner() {
    return this.content.join(", ");
  }
  toJSON() {
    return this.content.length ? this.content.map((e) => e.toJSON()) : null;
  }
  static fromJSON(e, t) {
    if (!t)
      return g.empty;
    if (!Array.isArray(t))
      throw new RangeError("Invalid input for Fragment.fromJSON");
    return new g(t.map(e.nodeFromJSON));
  }
  static fromArray(e) {
    if (!e.length)
      return g.empty;
    let t, n = 0;
    for (let i = 0; i < e.length; i++) {
      let s = e[i];
      n += s.nodeSize, i && s.isText && e[i - 1].sameMarkup(s) ? (t || (t = e.slice(0, i)), t[t.length - 1] = s.withText(t[t.length - 1].text + s.text)) : t && t.push(s);
    }
    return new g(t || e, n);
  }
  static from(e) {
    if (!e)
      return g.empty;
    if (e instanceof g)
      return e;
    if (Array.isArray(e))
      return this.fromArray(e);
    if (e.attrs)
      return new g([e], e.nodeSize);
    throw new RangeError("Can not convert " + e + " to a Fragment" + (e.nodesBetween ? " (looks like multiple versions of prosemirror-model were loaded)" : ""));
  }
}
g.empty = new g([], 0);
const Vt = { index: 0, offset: 0 };
function ht(r, e) {
  return Vt.index = r, Vt.offset = e, Vt;
}
function yt(r, e) {
  if (r === e)
    return !0;
  if (!(r && typeof r == "object") || !(e && typeof e == "object"))
    return !1;
  let t = Array.isArray(r);
  if (Array.isArray(e) != t)
    return !1;
  if (t) {
    if (r.length != e.length)
      return !1;
    for (let n = 0; n < r.length; n++)
      if (!yt(r[n], e[n]))
        return !1;
  } else {
    for (let n in r)
      if (!(n in e) || !yt(r[n], e[n]))
        return !1;
    for (let n in e)
      if (!(n in r))
        return !1;
  }
  return !0;
}
class M {
  constructor(e, t) {
    this.type = e, this.attrs = t;
  }
  addToSet(e) {
    let t, n = !1;
    for (let i = 0; i < e.length; i++) {
      let s = e[i];
      if (this.eq(s))
        return e;
      if (this.type.excludes(s.type))
        t || (t = e.slice(0, i));
      else {
        if (s.type.excludes(this.type))
          return e;
        !n && s.type.rank > this.type.rank && (t || (t = e.slice(0, i)), t.push(this), n = !0), t && t.push(s);
      }
    }
    return t || (t = e.slice()), n || t.push(this), t;
  }
  removeFromSet(e) {
    for (let t = 0; t < e.length; t++)
      if (this.eq(e[t]))
        return e.slice(0, t).concat(e.slice(t + 1));
    return e;
  }
  isInSet(e) {
    for (let t = 0; t < e.length; t++)
      if (this.eq(e[t]))
        return !0;
    return !1;
  }
  eq(e) {
    return this == e || this.type == e.type && yt(this.attrs, e.attrs);
  }
  toJSON() {
    let e = { type: this.type.name };
    for (let t in this.attrs) {
      e.attrs = this.attrs;
      break;
    }
    return e;
  }
  static fromJSON(e, t) {
    if (!t)
      throw new RangeError("Invalid input for Mark.fromJSON");
    let n = e.marks[t.type];
    if (!n)
      throw new RangeError(`There is no mark type ${t.type} in this schema`);
    return n.create(t.attrs);
  }
  static sameSet(e, t) {
    if (e == t)
      return !0;
    if (e.length != t.length)
      return !1;
    for (let n = 0; n < e.length; n++)
      if (!e[n].eq(t[n]))
        return !1;
    return !0;
  }
  static setFrom(e) {
    if (!e || Array.isArray(e) && e.length == 0)
      return M.none;
    if (e instanceof M)
      return [e];
    let t = e.slice();
    return t.sort((n, i) => n.type.rank - i.type.rank), t;
  }
}
M.none = [];
class kt extends Error {
}
class y {
  constructor(e, t, n) {
    this.content = e, this.openStart = t, this.openEnd = n;
  }
  get size() {
    return this.content.size - this.openStart - this.openEnd;
  }
  insertAt(e, t) {
    let n = Br(this.content, e + this.openStart, t);
    return n && new y(n, this.openStart, this.openEnd);
  }
  removeBetween(e, t) {
    return new y(zr(this.content, e + this.openStart, t + this.openStart), this.openStart, this.openEnd);
  }
  eq(e) {
    return this.content.eq(e.content) && this.openStart == e.openStart && this.openEnd == e.openEnd;
  }
  toString() {
    return this.content + "(" + this.openStart + "," + this.openEnd + ")";
  }
  toJSON() {
    if (!this.content.size)
      return null;
    let e = { content: this.content.toJSON() };
    return this.openStart > 0 && (e.openStart = this.openStart), this.openEnd > 0 && (e.openEnd = this.openEnd), e;
  }
  static fromJSON(e, t) {
    if (!t)
      return y.empty;
    let n = t.openStart || 0, i = t.openEnd || 0;
    if (typeof n != "number" || typeof i != "number")
      throw new RangeError("Invalid input for Slice.fromJSON");
    return new y(g.fromJSON(e, t.content), n, i);
  }
  static maxOpen(e, t = !0) {
    let n = 0, i = 0;
    for (let s = e.firstChild; s && !s.isLeaf && (t || !s.type.spec.isolating); s = s.firstChild)
      n++;
    for (let s = e.lastChild; s && !s.isLeaf && (t || !s.type.spec.isolating); s = s.lastChild)
      i++;
    return new y(e, n, i);
  }
}
y.empty = new y(g.empty, 0, 0);
function zr(r, e, t) {
  let { index: n, offset: i } = r.findIndex(e), s = r.maybeChild(n), { index: o, offset: l } = r.findIndex(t);
  if (i == e || s.isText) {
    if (l != t && !r.child(o).isText)
      throw new RangeError("Removing non-flat range");
    return r.cut(0, e).append(r.cut(t));
  }
  if (n != o)
    throw new RangeError("Removing non-flat range");
  return r.replaceChild(n, s.copy(zr(s.content, e - i - 1, t - i - 1)));
}
function Br(r, e, t, n) {
  let { index: i, offset: s } = r.findIndex(e), o = r.maybeChild(i);
  if (s == e || o.isText)
    return n && !n.canReplace(i, i, t) ? null : r.cut(0, e).append(t).append(r.cut(e));
  let l = Br(o.content, e - s - 1, t);
  return l && r.replaceChild(i, o.copy(l));
}
function Yi(r, e, t) {
  if (t.openStart > r.depth)
    throw new kt("Inserted content deeper than insertion position");
  if (r.depth - t.openStart != e.depth - t.openEnd)
    throw new kt("Inconsistent open depths");
  return vr(r, e, t, 0);
}
function vr(r, e, t, n) {
  let i = r.index(n), s = r.node(n);
  if (i == e.index(n) && n < r.depth - t.openStart) {
    let o = vr(r, e, t, n + 1);
    return s.copy(s.content.replaceChild(i, o));
  } else if (t.content.size)
    if (!t.openStart && !t.openEnd && r.depth == n && e.depth == n) {
      let o = r.parent, l = o.content;
      return Ne(o, l.cut(0, r.parentOffset).append(t.content).append(l.cut(e.parentOffset)));
    } else {
      let { start: o, end: l } = Xi(t, r);
      return Ne(s, Vr(r, o, l, e, n));
    }
  else
    return Ne(s, xt(r, e, n));
}
function Fr(r, e) {
  if (!e.type.compatibleContent(r.type))
    throw new kt("Cannot join " + e.type.name + " onto " + r.type.name);
}
function on(r, e, t) {
  let n = r.node(t);
  return Fr(n, e.node(t)), n;
}
function Oe(r, e) {
  let t = e.length - 1;
  t >= 0 && r.isText && r.sameMarkup(e[t]) ? e[t] = r.withText(e[t].text + r.text) : e.push(r);
}
function _e(r, e, t, n) {
  let i = (e || r).node(t), s = 0, o = e ? e.index(t) : i.childCount;
  r && (s = r.index(t), r.depth > t ? s++ : r.textOffset && (Oe(r.nodeAfter, n), s++));
  for (let l = s; l < o; l++)
    Oe(i.child(l), n);
  e && e.depth == t && e.textOffset && Oe(e.nodeBefore, n);
}
function Ne(r, e) {
  return r.type.checkContent(e), r.copy(e);
}
function Vr(r, e, t, n, i) {
  let s = r.depth > i && on(r, e, i + 1), o = n.depth > i && on(t, n, i + 1), l = [];
  return _e(null, r, i, l), s && o && e.index(i) == t.index(i) ? (Fr(s, o), Oe(Ne(s, Vr(r, e, t, n, i + 1)), l)) : (s && Oe(Ne(s, xt(r, e, i + 1)), l), _e(e, t, i, l), o && Oe(Ne(o, xt(t, n, i + 1)), l)), _e(n, null, i, l), new g(l);
}
function xt(r, e, t) {
  let n = [];
  if (_e(null, r, t, n), r.depth > t) {
    let i = on(r, e, t + 1);
    Oe(Ne(i, xt(r, e, t + 1)), n);
  }
  return _e(e, null, t, n), new g(n);
}
function Xi(r, e) {
  let t = e.depth - r.openStart, i = e.node(t).copy(r.content);
  for (let s = t - 1; s >= 0; s--)
    i = e.node(s).copy(g.from(i));
  return {
    start: i.resolveNoCache(r.openStart + t),
    end: i.resolveNoCache(i.content.size - r.openEnd - t)
  };
}
class rt {
  constructor(e, t, n) {
    this.pos = e, this.path = t, this.parentOffset = n, this.depth = t.length / 3 - 1;
  }
  resolveDepth(e) {
    return e == null ? this.depth : e < 0 ? this.depth + e : e;
  }
  get parent() {
    return this.node(this.depth);
  }
  get doc() {
    return this.node(0);
  }
  node(e) {
    return this.path[this.resolveDepth(e) * 3];
  }
  index(e) {
    return this.path[this.resolveDepth(e) * 3 + 1];
  }
  indexAfter(e) {
    return e = this.resolveDepth(e), this.index(e) + (e == this.depth && !this.textOffset ? 0 : 1);
  }
  start(e) {
    return e = this.resolveDepth(e), e == 0 ? 0 : this.path[e * 3 - 1] + 1;
  }
  end(e) {
    return e = this.resolveDepth(e), this.start(e) + this.node(e).content.size;
  }
  before(e) {
    if (e = this.resolveDepth(e), !e)
      throw new RangeError("There is no position before the top-level node");
    return e == this.depth + 1 ? this.pos : this.path[e * 3 - 1];
  }
  after(e) {
    if (e = this.resolveDepth(e), !e)
      throw new RangeError("There is no position after the top-level node");
    return e == this.depth + 1 ? this.pos : this.path[e * 3 - 1] + this.path[e * 3].nodeSize;
  }
  get textOffset() {
    return this.pos - this.path[this.path.length - 1];
  }
  get nodeAfter() {
    let e = this.parent, t = this.index(this.depth);
    if (t == e.childCount)
      return null;
    let n = this.pos - this.path[this.path.length - 1], i = e.child(t);
    return n ? e.child(t).cut(n) : i;
  }
  get nodeBefore() {
    let e = this.index(this.depth), t = this.pos - this.path[this.path.length - 1];
    return t ? this.parent.child(e).cut(0, t) : e == 0 ? null : this.parent.child(e - 1);
  }
  posAtIndex(e, t) {
    t = this.resolveDepth(t);
    let n = this.path[t * 3], i = t == 0 ? 0 : this.path[t * 3 - 1] + 1;
    for (let s = 0; s < e; s++)
      i += n.child(s).nodeSize;
    return i;
  }
  marks() {
    let e = this.parent, t = this.index();
    if (e.content.size == 0)
      return M.none;
    if (this.textOffset)
      return e.child(t).marks;
    let n = e.maybeChild(t - 1), i = e.maybeChild(t);
    if (!n) {
      let l = n;
      n = i, i = l;
    }
    let s = n.marks;
    for (var o = 0; o < s.length; o++)
      s[o].type.spec.inclusive === !1 && (!i || !s[o].isInSet(i.marks)) && (s = s[o--].removeFromSet(s));
    return s;
  }
  marksAcross(e) {
    let t = this.parent.maybeChild(this.index());
    if (!t || !t.isInline)
      return null;
    let n = t.marks, i = e.parent.maybeChild(e.index());
    for (var s = 0; s < n.length; s++)
      n[s].type.spec.inclusive === !1 && (!i || !n[s].isInSet(i.marks)) && (n = n[s--].removeFromSet(n));
    return n;
  }
  sharedDepth(e) {
    for (let t = this.depth; t > 0; t--)
      if (this.start(t) <= e && this.end(t) >= e)
        return t;
    return 0;
  }
  blockRange(e = this, t) {
    if (e.pos < this.pos)
      return e.blockRange(this);
    for (let n = this.depth - (this.parent.inlineContent || this.pos == e.pos ? 1 : 0); n >= 0; n--)
      if (e.pos <= this.end(n) && (!t || t(this.node(n))))
        return new Qi(this, e, n);
    return null;
  }
  sameParent(e) {
    return this.pos - this.parentOffset == e.pos - e.parentOffset;
  }
  max(e) {
    return e.pos > this.pos ? e : this;
  }
  min(e) {
    return e.pos < this.pos ? e : this;
  }
  toString() {
    let e = "";
    for (let t = 1; t <= this.depth; t++)
      e += (e ? "/" : "") + this.node(t).type.name + "_" + this.index(t - 1);
    return e + ":" + this.parentOffset;
  }
  static resolve(e, t) {
    if (!(t >= 0 && t <= e.content.size))
      throw new RangeError("Position " + t + " out of range");
    let n = [], i = 0, s = t;
    for (let o = e; ; ) {
      let { index: l, offset: a } = o.content.findIndex(s), h = s - a;
      if (n.push(o, l, i + a), !h || (o = o.child(l), o.isText))
        break;
      s = h - 1, i += a + 1;
    }
    return new rt(t, n, s);
  }
  static resolveCached(e, t) {
    for (let i = 0; i < Lt.length; i++) {
      let s = Lt[i];
      if (s.pos == t && s.doc == e)
        return s;
    }
    let n = Lt[Jt] = rt.resolve(e, t);
    return Jt = (Jt + 1) % Zi, n;
  }
}
let Lt = [], Jt = 0, Zi = 12;
class Qi {
  constructor(e, t, n) {
    this.$from = e, this.$to = t, this.depth = n;
  }
  get start() {
    return this.$from.before(this.depth + 1);
  }
  get end() {
    return this.$to.after(this.depth + 1);
  }
  get parent() {
    return this.$from.node(this.depth);
  }
  get startIndex() {
    return this.$from.index(this.depth);
  }
  get endIndex() {
    return this.$to.indexAfter(this.depth);
  }
}
const _i = /* @__PURE__ */ Object.create(null);
class te {
  constructor(e, t, n, i = M.none) {
    this.type = e, this.attrs = t, this.marks = i, this.content = n || g.empty;
  }
  get nodeSize() {
    return this.isLeaf ? 1 : 2 + this.content.size;
  }
  get childCount() {
    return this.content.childCount;
  }
  child(e) {
    return this.content.child(e);
  }
  maybeChild(e) {
    return this.content.maybeChild(e);
  }
  forEach(e) {
    this.content.forEach(e);
  }
  nodesBetween(e, t, n, i = 0) {
    this.content.nodesBetween(e, t, n, i, this);
  }
  descendants(e) {
    this.nodesBetween(0, this.content.size, e);
  }
  get textContent() {
    return this.isLeaf && this.type.spec.leafText ? this.type.spec.leafText(this) : this.textBetween(0, this.content.size, "");
  }
  textBetween(e, t, n, i) {
    return this.content.textBetween(e, t, n, i);
  }
  get firstChild() {
    return this.content.firstChild;
  }
  get lastChild() {
    return this.content.lastChild;
  }
  eq(e) {
    return this == e || this.sameMarkup(e) && this.content.eq(e.content);
  }
  sameMarkup(e) {
    return this.hasMarkup(e.type, e.attrs, e.marks);
  }
  hasMarkup(e, t, n) {
    return this.type == e && yt(this.attrs, t || e.defaultAttrs || _i) && M.sameSet(this.marks, n || M.none);
  }
  copy(e = null) {
    return e == this.content ? this : new te(this.type, this.attrs, e, this.marks);
  }
  mark(e) {
    return e == this.marks ? this : new te(this.type, this.attrs, this.content, e);
  }
  cut(e, t = this.content.size) {
    return e == 0 && t == this.content.size ? this : this.copy(this.content.cut(e, t));
  }
  slice(e, t = this.content.size, n = !1) {
    if (e == t)
      return y.empty;
    let i = this.resolve(e), s = this.resolve(t), o = n ? 0 : i.sharedDepth(t), l = i.start(o), h = i.node(o).content.cut(i.pos - l, s.pos - l);
    return new y(h, i.depth - o, s.depth - o);
  }
  replace(e, t, n) {
    return Yi(this.resolve(e), this.resolve(t), n);
  }
  nodeAt(e) {
    for (let t = this; ; ) {
      let { index: n, offset: i } = t.content.findIndex(e);
      if (t = t.maybeChild(n), !t)
        return null;
      if (i == e || t.isText)
        return t;
      e -= i + 1;
    }
  }
  childAfter(e) {
    let { index: t, offset: n } = this.content.findIndex(e);
    return { node: this.content.maybeChild(t), index: t, offset: n };
  }
  childBefore(e) {
    if (e == 0)
      return { node: null, index: 0, offset: 0 };
    let { index: t, offset: n } = this.content.findIndex(e);
    if (n < e)
      return { node: this.content.child(t), index: t, offset: n };
    let i = this.content.child(t - 1);
    return { node: i, index: t - 1, offset: n - i.nodeSize };
  }
  resolve(e) {
    return rt.resolveCached(this, e);
  }
  resolveNoCache(e) {
    return rt.resolve(this, e);
  }
  rangeHasMark(e, t, n) {
    let i = !1;
    return t > e && this.nodesBetween(e, t, (s) => (n.isInSet(s.marks) && (i = !0), !i)), i;
  }
  get isBlock() {
    return this.type.isBlock;
  }
  get isTextblock() {
    return this.type.isTextblock;
  }
  get inlineContent() {
    return this.type.inlineContent;
  }
  get isInline() {
    return this.type.isInline;
  }
  get isText() {
    return this.type.isText;
  }
  get isLeaf() {
    return this.type.isLeaf;
  }
  get isAtom() {
    return this.type.isAtom;
  }
  toString() {
    if (this.type.spec.toDebugString)
      return this.type.spec.toDebugString(this);
    let e = this.type.name;
    return this.content.size && (e += "(" + this.content.toStringInner() + ")"), Lr(this.marks, e);
  }
  contentMatchAt(e) {
    let t = this.type.contentMatch.matchFragment(this.content, 0, e);
    if (!t)
      throw new Error("Called contentMatchAt on a node with invalid content");
    return t;
  }
  canReplace(e, t, n = g.empty, i = 0, s = n.childCount) {
    let o = this.contentMatchAt(e).matchFragment(n, i, s), l = o && o.matchFragment(this.content, t);
    if (!l || !l.validEnd)
      return !1;
    for (let a = i; a < s; a++)
      if (!this.type.allowsMarks(n.child(a).marks))
        return !1;
    return !0;
  }
  canReplaceWith(e, t, n, i) {
    if (i && !this.type.allowsMarks(i))
      return !1;
    let s = this.contentMatchAt(e).matchType(n), o = s && s.matchFragment(this.content, t);
    return o ? o.validEnd : !1;
  }
  canAppend(e) {
    return e.content.size ? this.canReplace(this.childCount, this.childCount, e.content) : this.type.compatibleContent(e.type);
  }
  check() {
    this.type.checkContent(this.content);
    let e = M.none;
    for (let t = 0; t < this.marks.length; t++)
      e = this.marks[t].addToSet(e);
    if (!M.sameSet(e, this.marks))
      throw new RangeError(`Invalid collection of marks for node ${this.type.name}: ${this.marks.map((t) => t.type.name)}`);
    this.content.forEach((t) => t.check());
  }
  toJSON() {
    let e = { type: this.type.name };
    for (let t in this.attrs) {
      e.attrs = this.attrs;
      break;
    }
    return this.content.size && (e.content = this.content.toJSON()), this.marks.length && (e.marks = this.marks.map((t) => t.toJSON())), e;
  }
  static fromJSON(e, t) {
    if (!t)
      throw new RangeError("Invalid input for Node.fromJSON");
    let n = null;
    if (t.marks) {
      if (!Array.isArray(t.marks))
        throw new RangeError("Invalid mark data for Node.fromJSON");
      n = t.marks.map(e.markFromJSON);
    }
    if (t.type == "text") {
      if (typeof t.text != "string")
        throw new RangeError("Invalid text node in JSON");
      return e.text(t.text, n);
    }
    let i = g.fromJSON(e, t.content);
    return e.nodeType(t.type).create(t.attrs, i, n);
  }
}
te.prototype.text = void 0;
class St extends te {
  constructor(e, t, n, i) {
    if (super(e, t, null, i), !n)
      throw new RangeError("Empty text nodes are not allowed");
    this.text = n;
  }
  toString() {
    return this.type.spec.toDebugString ? this.type.spec.toDebugString(this) : Lr(this.marks, JSON.stringify(this.text));
  }
  get textContent() {
    return this.text;
  }
  textBetween(e, t) {
    return this.text.slice(e, t);
  }
  get nodeSize() {
    return this.text.length;
  }
  mark(e) {
    return e == this.marks ? this : new St(this.type, this.attrs, this.text, e);
  }
  withText(e) {
    return e == this.text ? this : new St(this.type, this.attrs, e, this.marks);
  }
  cut(e = 0, t = this.text.length) {
    return e == 0 && t == this.text.length ? this : this.withText(this.text.slice(e, t));
  }
  eq(e) {
    return this.sameMarkup(e) && this.text == e.text;
  }
  toJSON() {
    let e = super.toJSON();
    return e.text = this.text, e;
  }
}
function Lr(r, e) {
  for (let t = r.length - 1; t >= 0; t--)
    e = r[t].type.name + "(" + e + ")";
  return e;
}
class De {
  constructor(e) {
    this.validEnd = e, this.next = [], this.wrapCache = [];
  }
  static parse(e, t) {
    let n = new es(e, t);
    if (n.next == null)
      return De.empty;
    let i = Jr(n);
    n.next && n.err("Unexpected trailing text");
    let s = as(ls(i));
    return hs(s, n), s;
  }
  matchType(e) {
    for (let t = 0; t < this.next.length; t++)
      if (this.next[t].type == e)
        return this.next[t].next;
    return null;
  }
  matchFragment(e, t = 0, n = e.childCount) {
    let i = this;
    for (let s = t; i && s < n; s++)
      i = i.matchType(e.child(s).type);
    return i;
  }
  get inlineContent() {
    return this.next.length != 0 && this.next[0].type.isInline;
  }
  get defaultType() {
    for (let e = 0; e < this.next.length; e++) {
      let { type: t } = this.next[e];
      if (!(t.isText || t.hasRequiredAttrs()))
        return t;
    }
    return null;
  }
  compatible(e) {
    for (let t = 0; t < this.next.length; t++)
      for (let n = 0; n < e.next.length; n++)
        if (this.next[t].type == e.next[n].type)
          return !0;
    return !1;
  }
  fillBefore(e, t = !1, n = 0) {
    let i = [this];
    function s(o, l) {
      let a = o.matchFragment(e, n);
      if (a && (!t || a.validEnd))
        return g.from(l.map((h) => h.createAndFill()));
      for (let h = 0; h < o.next.length; h++) {
        let { type: c, next: f } = o.next[h];
        if (!(c.isText || c.hasRequiredAttrs()) && i.indexOf(f) == -1) {
          i.push(f);
          let d = s(f, l.concat(c));
          if (d)
            return d;
        }
      }
      return null;
    }
    return s(this, []);
  }
  findWrapping(e) {
    for (let n = 0; n < this.wrapCache.length; n += 2)
      if (this.wrapCache[n] == e)
        return this.wrapCache[n + 1];
    let t = this.computeWrapping(e);
    return this.wrapCache.push(e, t), t;
  }
  computeWrapping(e) {
    let t = /* @__PURE__ */ Object.create(null), n = [{ match: this, type: null, via: null }];
    for (; n.length; ) {
      let i = n.shift(), s = i.match;
      if (s.matchType(e)) {
        let o = [];
        for (let l = i; l.type; l = l.via)
          o.push(l.type);
        return o.reverse();
      }
      for (let o = 0; o < s.next.length; o++) {
        let { type: l, next: a } = s.next[o];
        !l.isLeaf && !l.hasRequiredAttrs() && !(l.name in t) && (!i.type || a.validEnd) && (n.push({ match: l.contentMatch, type: l, via: i }), t[l.name] = !0);
      }
    }
    return null;
  }
  get edgeCount() {
    return this.next.length;
  }
  edge(e) {
    if (e >= this.next.length)
      throw new RangeError(`There's no ${e}th edge in this content match`);
    return this.next[e];
  }
  toString() {
    let e = [];
    function t(n) {
      e.push(n);
      for (let i = 0; i < n.next.length; i++)
        e.indexOf(n.next[i].next) == -1 && t(n.next[i].next);
    }
    return t(this), e.map((n, i) => {
      let s = i + (n.validEnd ? "*" : " ") + " ";
      for (let o = 0; o < n.next.length; o++)
        s += (o ? ", " : "") + n.next[o].type.name + "->" + e.indexOf(n.next[o].next);
      return s;
    }).join(`
`);
  }
}
De.empty = new De(!0);
class es {
  constructor(e, t) {
    this.string = e, this.nodeTypes = t, this.inline = null, this.pos = 0, this.tokens = e.split(/\s*(?=\b|\W|$)/), this.tokens[this.tokens.length - 1] == "" && this.tokens.pop(), this.tokens[0] == "" && this.tokens.shift();
  }
  get next() {
    return this.tokens[this.pos];
  }
  eat(e) {
    return this.next == e && (this.pos++ || !0);
  }
  err(e) {
    throw new SyntaxError(e + " (in content expression '" + this.string + "')");
  }
}
function Jr(r) {
  let e = [];
  do
    e.push(ts(r));
  while (r.eat("|"));
  return e.length == 1 ? e[0] : { type: "choice", exprs: e };
}
function ts(r) {
  let e = [];
  do
    e.push(ns(r));
  while (r.next && r.next != ")" && r.next != "|");
  return e.length == 1 ? e[0] : { type: "seq", exprs: e };
}
function ns(r) {
  let e = ss(r);
  for (; ; )
    if (r.eat("+"))
      e = { type: "plus", expr: e };
    else if (r.eat("*"))
      e = { type: "star", expr: e };
    else if (r.eat("?"))
      e = { type: "opt", expr: e };
    else if (r.eat("{"))
      e = rs(r, e);
    else
      break;
  return e;
}
function vn(r) {
  /\D/.test(r.next) && r.err("Expected number, got '" + r.next + "'");
  let e = Number(r.next);
  return r.pos++, e;
}
function rs(r, e) {
  let t = vn(r), n = t;
  return r.eat(",") && (r.next != "}" ? n = vn(r) : n = -1), r.eat("}") || r.err("Unclosed braced range"), { type: "range", min: t, max: n, expr: e };
}
function is(r, e) {
  let t = r.nodeTypes, n = t[e];
  if (n)
    return [n];
  let i = [];
  for (let s in t) {
    let o = t[s];
    o.groups.indexOf(e) > -1 && i.push(o);
  }
  return i.length == 0 && r.err("No node type or group '" + e + "' found"), i;
}
function ss(r) {
  if (r.eat("(")) {
    let e = Jr(r);
    return r.eat(")") || r.err("Missing closing paren"), e;
  } else if (/\W/.test(r.next))
    r.err("Unexpected token '" + r.next + "'");
  else {
    let e = is(r, r.next).map((t) => (r.inline == null ? r.inline = t.isInline : r.inline != t.isInline && r.err("Mixing inline and block content"), { type: "name", value: t }));
    return r.pos++, e.length == 1 ? e[0] : { type: "choice", exprs: e };
  }
}
function ls(r) {
  let e = [[]];
  return i(s(r, 0), t()), e;
  function t() {
    return e.push([]) - 1;
  }
  function n(o, l, a) {
    let h = { term: a, to: l };
    return e[o].push(h), h;
  }
  function i(o, l) {
    o.forEach((a) => a.to = l);
  }
  function s(o, l) {
    if (o.type == "choice")
      return o.exprs.reduce((a, h) => a.concat(s(h, l)), []);
    if (o.type == "seq")
      for (let a = 0; ; a++) {
        let h = s(o.exprs[a], l);
        if (a == o.exprs.length - 1)
          return h;
        i(h, l = t());
      }
    else if (o.type == "star") {
      let a = t();
      return n(l, a), i(s(o.expr, a), a), [n(a)];
    } else if (o.type == "plus") {
      let a = t();
      return i(s(o.expr, l), a), i(s(o.expr, a), a), [n(a)];
    } else {
      if (o.type == "opt")
        return [n(l)].concat(s(o.expr, l));
      if (o.type == "range") {
        let a = l;
        for (let h = 0; h < o.min; h++) {
          let c = t();
          i(s(o.expr, a), c), a = c;
        }
        if (o.max == -1)
          i(s(o.expr, a), a);
        else
          for (let h = o.min; h < o.max; h++) {
            let c = t();
            n(a, c), i(s(o.expr, a), c), a = c;
          }
        return [n(a)];
      } else {
        if (o.type == "name")
          return [n(l, void 0, o.value)];
        throw new Error("Unknown expr type");
      }
    }
  }
}
function Wr(r, e) {
  return e - r;
}
function Fn(r, e) {
  let t = [];
  return n(e), t.sort(Wr);
  function n(i) {
    let s = r[i];
    if (s.length == 1 && !s[0].term)
      return n(s[0].to);
    t.push(i);
    for (let o = 0; o < s.length; o++) {
      let { term: l, to: a } = s[o];
      !l && t.indexOf(a) == -1 && n(a);
    }
  }
}
function as(r) {
  let e = /* @__PURE__ */ Object.create(null);
  return t(Fn(r, 0));
  function t(n) {
    let i = [];
    n.forEach((o) => {
      r[o].forEach(({ term: l, to: a }) => {
        if (!l)
          return;
        let h;
        for (let c = 0; c < i.length; c++)
          i[c][0] == l && (h = i[c][1]);
        Fn(r, a).forEach((c) => {
          h || i.push([l, h = []]), h.indexOf(c) == -1 && h.push(c);
        });
      });
    });
    let s = e[n.join(",")] = new De(n.indexOf(r.length - 1) > -1);
    for (let o = 0; o < i.length; o++) {
      let l = i[o][1].sort(Wr);
      s.next.push({ type: i[o][0], next: e[l.join(",")] || t(l) });
    }
    return s;
  }
}
function hs(r, e) {
  for (let t = 0, n = [r]; t < n.length; t++) {
    let i = n[t], s = !i.validEnd, o = [];
    for (let l = 0; l < i.next.length; l++) {
      let { type: a, next: h } = i.next[l];
      o.push(a.name), s && !(a.isText || a.hasRequiredAttrs()) && (s = !1), n.indexOf(h) == -1 && n.push(h);
    }
    s && e.err("Only non-generatable nodes (" + o.join(", ") + ") in a required position (see https://prosemirror.net/docs/guide/#generatable)");
  }
}
function qr(r) {
  let e = /* @__PURE__ */ Object.create(null);
  for (let t in r) {
    let n = r[t];
    if (!n.hasDefault)
      return null;
    e[t] = n.default;
  }
  return e;
}
function Kr(r, e) {
  let t = /* @__PURE__ */ Object.create(null);
  for (let n in r) {
    let i = e && e[n];
    if (i === void 0) {
      let s = r[n];
      if (s.hasDefault)
        i = s.default;
      else
        throw new RangeError("No value supplied for attribute " + n);
    }
    t[n] = i;
  }
  return t;
}
function Hr(r) {
  let e = /* @__PURE__ */ Object.create(null);
  if (r)
    for (let t in r)
      e[t] = new cs(r[t]);
  return e;
}
class bt {
  constructor(e, t, n) {
    this.name = e, this.schema = t, this.spec = n, this.markSet = null, this.groups = n.group ? n.group.split(" ") : [], this.attrs = Hr(n.attrs), this.defaultAttrs = qr(this.attrs), this.contentMatch = null, this.inlineContent = null, this.isBlock = !(n.inline || e == "text"), this.isText = e == "text";
  }
  get isInline() {
    return !this.isBlock;
  }
  get isTextblock() {
    return this.isBlock && this.inlineContent;
  }
  get isLeaf() {
    return this.contentMatch == De.empty;
  }
  get isAtom() {
    return this.isLeaf || !!this.spec.atom;
  }
  get whitespace() {
    return this.spec.whitespace || (this.spec.code ? "pre" : "normal");
  }
  hasRequiredAttrs() {
    for (let e in this.attrs)
      if (this.attrs[e].isRequired)
        return !0;
    return !1;
  }
  compatibleContent(e) {
    return this == e || this.contentMatch.compatible(e.contentMatch);
  }
  computeAttrs(e) {
    return !e && this.defaultAttrs ? this.defaultAttrs : Kr(this.attrs, e);
  }
  create(e = null, t, n) {
    if (this.isText)
      throw new Error("NodeType.create can't construct text nodes");
    return new te(this, this.computeAttrs(e), g.from(t), M.setFrom(n));
  }
  createChecked(e = null, t, n) {
    return t = g.from(t), this.checkContent(t), new te(this, this.computeAttrs(e), t, M.setFrom(n));
  }
  createAndFill(e = null, t, n) {
    if (e = this.computeAttrs(e), t = g.from(t), t.size) {
      let o = this.contentMatch.fillBefore(t);
      if (!o)
        return null;
      t = o.append(t);
    }
    let i = this.contentMatch.matchFragment(t), s = i && i.fillBefore(g.empty, !0);
    return s ? new te(this, e, t.append(s), M.setFrom(n)) : null;
  }
  validContent(e) {
    let t = this.contentMatch.matchFragment(e);
    if (!t || !t.validEnd)
      return !1;
    for (let n = 0; n < e.childCount; n++)
      if (!this.allowsMarks(e.child(n).marks))
        return !1;
    return !0;
  }
  checkContent(e) {
    if (!this.validContent(e))
      throw new RangeError(`Invalid content for node ${this.name}: ${e.toString().slice(0, 50)}`);
  }
  allowsMarkType(e) {
    return this.markSet == null || this.markSet.indexOf(e) > -1;
  }
  allowsMarks(e) {
    if (this.markSet == null)
      return !0;
    for (let t = 0; t < e.length; t++)
      if (!this.allowsMarkType(e[t].type))
        return !1;
    return !0;
  }
  allowedMarks(e) {
    if (this.markSet == null)
      return e;
    let t;
    for (let n = 0; n < e.length; n++)
      this.allowsMarkType(e[n].type) ? t && t.push(e[n]) : t || (t = e.slice(0, n));
    return t ? t.length ? t : M.none : e;
  }
  static compile(e, t) {
    let n = /* @__PURE__ */ Object.create(null);
    e.forEach((s, o) => n[s] = new bt(s, t, o));
    let i = t.spec.topNode || "doc";
    if (!n[i])
      throw new RangeError("Schema is missing its top node type ('" + i + "')");
    if (!n.text)
      throw new RangeError("Every schema needs a 'text' type");
    for (let s in n.text.attrs)
      throw new RangeError("The text node type should not have attributes");
    return n;
  }
}
class cs {
  constructor(e) {
    this.hasDefault = Object.prototype.hasOwnProperty.call(e, "default"), this.default = e.default;
  }
  get isRequired() {
    return !this.hasDefault;
  }
}
class Et {
  constructor(e, t, n, i) {
    this.name = e, this.rank = t, this.schema = n, this.spec = i, this.attrs = Hr(i.attrs), this.excluded = null;
    let s = qr(this.attrs);
    this.instance = s ? new M(this, s) : null;
  }
  create(e = null) {
    return !e && this.instance ? this.instance : new M(this, Kr(this.attrs, e));
  }
  static compile(e, t) {
    let n = /* @__PURE__ */ Object.create(null), i = 0;
    return e.forEach((s, o) => n[s] = new Et(s, i++, t, o)), n;
  }
  removeFromSet(e) {
    for (var t = 0; t < e.length; t++)
      e[t].type == this && (e = e.slice(0, t).concat(e.slice(t + 1)), t--);
    return e;
  }
  isInSet(e) {
    for (let t = 0; t < e.length; t++)
      if (e[t].type == this)
        return e[t];
  }
  excludes(e) {
    return this.excluded.indexOf(e) > -1;
  }
}
class fs {
  constructor(e) {
    this.cached = /* @__PURE__ */ Object.create(null);
    let t = this.spec = {};
    for (let i in e)
      t[i] = e[i];
    t.nodes = T.from(e.nodes), t.marks = T.from(e.marks || {}), this.nodes = bt.compile(this.spec.nodes, this), this.marks = Et.compile(this.spec.marks, this);
    let n = /* @__PURE__ */ Object.create(null);
    for (let i in this.nodes) {
      if (i in this.marks)
        throw new RangeError(i + " can not be both a node and a mark");
      let s = this.nodes[i], o = s.spec.content || "", l = s.spec.marks;
      s.contentMatch = n[o] || (n[o] = De.parse(o, this.nodes)), s.inlineContent = s.contentMatch.inlineContent, s.markSet = l == "_" ? null : l ? Vn(this, l.split(" ")) : l == "" || !s.inlineContent ? [] : null;
    }
    for (let i in this.marks) {
      let s = this.marks[i], o = s.spec.excludes;
      s.excluded = o == null ? [s] : o == "" ? [] : Vn(this, o.split(" "));
    }
    this.nodeFromJSON = this.nodeFromJSON.bind(this), this.markFromJSON = this.markFromJSON.bind(this), this.topNodeType = this.nodes[this.spec.topNode || "doc"], this.cached.wrappings = /* @__PURE__ */ Object.create(null);
  }
  node(e, t = null, n, i) {
    if (typeof e == "string")
      e = this.nodeType(e);
    else if (e instanceof bt) {
      if (e.schema != this)
        throw new RangeError("Node type from different schema used (" + e.name + ")");
    } else
      throw new RangeError("Invalid node type: " + e);
    return e.createChecked(t, n, i);
  }
  text(e, t) {
    let n = this.nodes.text;
    return new St(n, n.defaultAttrs, e, M.setFrom(t));
  }
  mark(e, t) {
    return typeof e == "string" && (e = this.marks[e]), e.create(t);
  }
  nodeFromJSON(e) {
    return te.fromJSON(this, e);
  }
  markFromJSON(e) {
    return M.fromJSON(this, e);
  }
  nodeType(e) {
    let t = this.nodes[e];
    if (!t)
      throw new RangeError("Unknown node type: " + e);
    return t;
  }
}
function Vn(r, e) {
  let t = [];
  for (let n = 0; n < e.length; n++) {
    let i = e[n], s = r.marks[i], o = s;
    if (s)
      t.push(s);
    else
      for (let l in r.marks) {
        let a = r.marks[l];
        (i == "_" || a.spec.group && a.spec.group.split(" ").indexOf(i) > -1) && t.push(o = a);
      }
    if (!o)
      throw new SyntaxError("Unknown mark type: '" + e[n] + "'");
  }
  return t;
}
class it {
  constructor(e, t) {
    this.schema = e, this.rules = t, this.tags = [], this.styles = [], t.forEach((n) => {
      n.tag ? this.tags.push(n) : n.style && this.styles.push(n);
    }), this.normalizeLists = !this.tags.some((n) => {
      if (!/^(ul|ol)\b/.test(n.tag) || !n.node)
        return !1;
      let i = e.nodes[n.node];
      return i.contentMatch.matchType(i);
    });
  }
  parse(e, t = {}) {
    let n = new Jn(this, t, !1);
    return n.addAll(e, t.from, t.to), n.finish();
  }
  parseSlice(e, t = {}) {
    let n = new Jn(this, t, !0);
    return n.addAll(e, t.from, t.to), y.maxOpen(n.finish());
  }
  matchTag(e, t, n) {
    for (let i = n ? this.tags.indexOf(n) + 1 : 0; i < this.tags.length; i++) {
      let s = this.tags[i];
      if (ps(e, s.tag) && (s.namespace === void 0 || e.namespaceURI == s.namespace) && (!s.context || t.matchesContext(s.context))) {
        if (s.getAttrs) {
          let o = s.getAttrs(e);
          if (o === !1)
            continue;
          s.attrs = o || void 0;
        }
        return s;
      }
    }
  }
  matchStyle(e, t, n, i) {
    for (let s = i ? this.styles.indexOf(i) + 1 : 0; s < this.styles.length; s++) {
      let o = this.styles[s], l = o.style;
      if (!(l.indexOf(e) != 0 || o.context && !n.matchesContext(o.context) || l.length > e.length && (l.charCodeAt(e.length) != 61 || l.slice(e.length + 1) != t))) {
        if (o.getAttrs) {
          let a = o.getAttrs(t);
          if (a === !1)
            continue;
          o.attrs = a || void 0;
        }
        return o;
      }
    }
  }
  static schemaRules(e) {
    let t = [];
    function n(i) {
      let s = i.priority == null ? 50 : i.priority, o = 0;
      for (; o < t.length; o++) {
        let l = t[o];
        if ((l.priority == null ? 50 : l.priority) < s)
          break;
      }
      t.splice(o, 0, i);
    }
    for (let i in e.marks) {
      let s = e.marks[i].spec.parseDOM;
      s && s.forEach((o) => {
        n(o = Wn(o)), o.mark = i;
      });
    }
    for (let i in e.nodes) {
      let s = e.nodes[i].spec.parseDOM;
      s && s.forEach((o) => {
        n(o = Wn(o)), o.node = i;
      });
    }
    return t;
  }
  static fromSchema(e) {
    return e.cached.domParser || (e.cached.domParser = new it(e, it.schemaRules(e)));
  }
}
const $r = {
  address: !0,
  article: !0,
  aside: !0,
  blockquote: !0,
  canvas: !0,
  dd: !0,
  div: !0,
  dl: !0,
  fieldset: !0,
  figcaption: !0,
  figure: !0,
  footer: !0,
  form: !0,
  h1: !0,
  h2: !0,
  h3: !0,
  h4: !0,
  h5: !0,
  h6: !0,
  header: !0,
  hgroup: !0,
  hr: !0,
  li: !0,
  noscript: !0,
  ol: !0,
  output: !0,
  p: !0,
  pre: !0,
  section: !0,
  table: !0,
  tfoot: !0,
  ul: !0
}, ds = {
  head: !0,
  noscript: !0,
  object: !0,
  script: !0,
  style: !0,
  title: !0
}, Ur = { ol: !0, ul: !0 }, Mt = 1, Ct = 2, et = 4;
function Ln(r, e, t) {
  return e != null ? (e ? Mt : 0) | (e === "full" ? Ct : 0) : r && r.whitespace == "pre" ? Mt | Ct : t & ~et;
}
class ct {
  constructor(e, t, n, i, s, o, l) {
    this.type = e, this.attrs = t, this.marks = n, this.pendingMarks = i, this.solid = s, this.options = l, this.content = [], this.activeMarks = M.none, this.stashMarks = [], this.match = o || (l & et ? null : e.contentMatch);
  }
  findWrapping(e) {
    if (!this.match) {
      if (!this.type)
        return [];
      let t = this.type.contentMatch.fillBefore(g.from(e));
      if (t)
        this.match = this.type.contentMatch.matchFragment(t);
      else {
        let n = this.type.contentMatch, i;
        return (i = n.findWrapping(e.type)) ? (this.match = n, i) : null;
      }
    }
    return this.match.findWrapping(e.type);
  }
  finish(e) {
    if (!(this.options & Mt)) {
      let n = this.content[this.content.length - 1], i;
      if (n && n.isText && (i = /[ \t\r\n\u000c]+$/.exec(n.text))) {
        let s = n;
        n.text.length == i[0].length ? this.content.pop() : this.content[this.content.length - 1] = s.withText(s.text.slice(0, s.text.length - i[0].length));
      }
    }
    let t = g.from(this.content);
    return !e && this.match && (t = t.append(this.match.fillBefore(g.empty, !0))), this.type ? this.type.create(this.attrs, t, this.marks) : t;
  }
  popFromStashMark(e) {
    for (let t = this.stashMarks.length - 1; t >= 0; t--)
      if (e.eq(this.stashMarks[t]))
        return this.stashMarks.splice(t, 1)[0];
  }
  applyPending(e) {
    for (let t = 0, n = this.pendingMarks; t < n.length; t++) {
      let i = n[t];
      (this.type ? this.type.allowsMarkType(i.type) : gs(i.type, e)) && !i.isInSet(this.activeMarks) && (this.activeMarks = i.addToSet(this.activeMarks), this.pendingMarks = i.removeFromSet(this.pendingMarks));
    }
  }
  inlineContext(e) {
    return this.type ? this.type.inlineContent : this.content.length ? this.content[0].isInline : e.parentNode && !$r.hasOwnProperty(e.parentNode.nodeName.toLowerCase());
  }
}
class Jn {
  constructor(e, t, n) {
    this.parser = e, this.options = t, this.isOpen = n, this.open = 0;
    let i = t.topNode, s, o = Ln(null, t.preserveWhitespace, 0) | (n ? et : 0);
    i ? s = new ct(i.type, i.attrs, M.none, M.none, !0, t.topMatch || i.type.contentMatch, o) : n ? s = new ct(null, null, M.none, M.none, !0, null, o) : s = new ct(e.schema.topNodeType, null, M.none, M.none, !0, null, o), this.nodes = [s], this.find = t.findPositions, this.needsBlock = !1;
  }
  get top() {
    return this.nodes[this.open];
  }
  addDOM(e) {
    if (e.nodeType == 3)
      this.addTextNode(e);
    else if (e.nodeType == 1) {
      let t = e.getAttribute("style"), n = t ? this.readStyles(ms(t)) : null, i = this.top;
      if (n != null)
        for (let s = 0; s < n.length; s++)
          this.addPendingMark(n[s]);
      if (this.addElement(e), n != null)
        for (let s = 0; s < n.length; s++)
          this.removePendingMark(n[s], i);
    }
  }
  addTextNode(e) {
    let t = e.nodeValue, n = this.top;
    if (n.options & Ct || n.inlineContext(e) || /[^ \t\r\n\u000c]/.test(t)) {
      if (n.options & Mt)
        n.options & Ct ? t = t.replace(/\r\n?/g, `
`) : t = t.replace(/\r?\n|\r/g, " ");
      else if (t = t.replace(/[ \t\r\n\u000c]+/g, " "), /^[ \t\r\n\u000c]/.test(t) && this.open == this.nodes.length - 1) {
        let i = n.content[n.content.length - 1], s = e.previousSibling;
        (!i || s && s.nodeName == "BR" || i.isText && /[ \t\r\n\u000c]$/.test(i.text)) && (t = t.slice(1));
      }
      t && this.insertNode(this.parser.schema.text(t)), this.findInText(e);
    } else
      this.findInside(e);
  }
  addElement(e, t) {
    let n = e.nodeName.toLowerCase(), i;
    Ur.hasOwnProperty(n) && this.parser.normalizeLists && us(e);
    let s = this.options.ruleFromNode && this.options.ruleFromNode(e) || (i = this.parser.matchTag(e, this, t));
    if (s ? s.ignore : ds.hasOwnProperty(n))
      this.findInside(e), this.ignoreFallback(e);
    else if (!s || s.skip || s.closeParent) {
      s && s.closeParent ? this.open = Math.max(0, this.open - 1) : s && s.skip.nodeType && (e = s.skip);
      let o, l = this.top, a = this.needsBlock;
      if ($r.hasOwnProperty(n))
        l.content.length && l.content[0].isInline && this.open && (this.open--, l = this.top), o = !0, l.type || (this.needsBlock = !0);
      else if (!e.firstChild) {
        this.leafFallback(e);
        return;
      }
      this.addAll(e), o && this.sync(l), this.needsBlock = a;
    } else
      this.addElementByRule(e, s, s.consuming === !1 ? i : void 0);
  }
  leafFallback(e) {
    e.nodeName == "BR" && this.top.type && this.top.type.inlineContent && this.addTextNode(e.ownerDocument.createTextNode(`
`));
  }
  ignoreFallback(e) {
    e.nodeName == "BR" && (!this.top.type || !this.top.type.inlineContent) && this.findPlace(this.parser.schema.text("-"));
  }
  readStyles(e) {
    let t = M.none;
    e:
      for (let n = 0; n < e.length; n += 2)
        for (let i = void 0; ; ) {
          let s = this.parser.matchStyle(e[n], e[n + 1], this, i);
          if (!s)
            continue e;
          if (s.ignore)
            return null;
          if (t = this.parser.schema.marks[s.mark].create(s.attrs).addToSet(t), s.consuming === !1)
            i = s;
          else
            break;
        }
    return t;
  }
  addElementByRule(e, t, n) {
    let i, s, o;
    t.node ? (s = this.parser.schema.nodes[t.node], s.isLeaf ? this.insertNode(s.create(t.attrs)) || this.leafFallback(e) : i = this.enter(s, t.attrs || null, t.preserveWhitespace)) : (o = this.parser.schema.marks[t.mark].create(t.attrs), this.addPendingMark(o));
    let l = this.top;
    if (s && s.isLeaf)
      this.findInside(e);
    else if (n)
      this.addElement(e, n);
    else if (t.getContent)
      this.findInside(e), t.getContent(e, this.parser.schema).forEach((a) => this.insertNode(a));
    else {
      let a = e;
      typeof t.contentElement == "string" ? a = e.querySelector(t.contentElement) : typeof t.contentElement == "function" ? a = t.contentElement(e) : t.contentElement && (a = t.contentElement), this.findAround(e, a, !0), this.addAll(a);
    }
    i && this.sync(l) && this.open--, o && this.removePendingMark(o, l);
  }
  addAll(e, t, n) {
    let i = t || 0;
    for (let s = t ? e.childNodes[t] : e.firstChild, o = n == null ? null : e.childNodes[n]; s != o; s = s.nextSibling, ++i)
      this.findAtPoint(e, i), this.addDOM(s);
    this.findAtPoint(e, i);
  }
  findPlace(e) {
    let t, n;
    for (let i = this.open; i >= 0; i--) {
      let s = this.nodes[i], o = s.findWrapping(e);
      if (o && (!t || t.length > o.length) && (t = o, n = s, !o.length) || s.solid)
        break;
    }
    if (!t)
      return !1;
    this.sync(n);
    for (let i = 0; i < t.length; i++)
      this.enterInner(t[i], null, !1);
    return !0;
  }
  insertNode(e) {
    if (e.isInline && this.needsBlock && !this.top.type) {
      let t = this.textblockFromContext();
      t && this.enterInner(t);
    }
    if (this.findPlace(e)) {
      this.closeExtra();
      let t = this.top;
      t.applyPending(e.type), t.match && (t.match = t.match.matchType(e.type));
      let n = t.activeMarks;
      for (let i = 0; i < e.marks.length; i++)
        (!t.type || t.type.allowsMarkType(e.marks[i].type)) && (n = e.marks[i].addToSet(n));
      return t.content.push(e.mark(n)), !0;
    }
    return !1;
  }
  enter(e, t, n) {
    let i = this.findPlace(e.create(t));
    return i && this.enterInner(e, t, !0, n), i;
  }
  enterInner(e, t = null, n = !1, i) {
    this.closeExtra();
    let s = this.top;
    s.applyPending(e), s.match = s.match && s.match.matchType(e);
    let o = Ln(e, i, s.options);
    s.options & et && s.content.length == 0 && (o |= et), this.nodes.push(new ct(e, t, s.activeMarks, s.pendingMarks, n, null, o)), this.open++;
  }
  closeExtra(e = !1) {
    let t = this.nodes.length - 1;
    if (t > this.open) {
      for (; t > this.open; t--)
        this.nodes[t - 1].content.push(this.nodes[t].finish(e));
      this.nodes.length = this.open + 1;
    }
  }
  finish() {
    return this.open = 0, this.closeExtra(this.isOpen), this.nodes[0].finish(this.isOpen || this.options.topOpen);
  }
  sync(e) {
    for (let t = this.open; t >= 0; t--)
      if (this.nodes[t] == e)
        return this.open = t, !0;
    return !1;
  }
  get currentPos() {
    this.closeExtra();
    let e = 0;
    for (let t = this.open; t >= 0; t--) {
      let n = this.nodes[t].content;
      for (let i = n.length - 1; i >= 0; i--)
        e += n[i].nodeSize;
      t && e++;
    }
    return e;
  }
  findAtPoint(e, t) {
    if (this.find)
      for (let n = 0; n < this.find.length; n++)
        this.find[n].node == e && this.find[n].offset == t && (this.find[n].pos = this.currentPos);
  }
  findInside(e) {
    if (this.find)
      for (let t = 0; t < this.find.length; t++)
        this.find[t].pos == null && e.nodeType == 1 && e.contains(this.find[t].node) && (this.find[t].pos = this.currentPos);
  }
  findAround(e, t, n) {
    if (e != t && this.find)
      for (let i = 0; i < this.find.length; i++)
        this.find[i].pos == null && e.nodeType == 1 && e.contains(this.find[i].node) && t.compareDocumentPosition(this.find[i].node) & (n ? 2 : 4) && (this.find[i].pos = this.currentPos);
  }
  findInText(e) {
    if (this.find)
      for (let t = 0; t < this.find.length; t++)
        this.find[t].node == e && (this.find[t].pos = this.currentPos - (e.nodeValue.length - this.find[t].offset));
  }
  matchesContext(e) {
    if (e.indexOf("|") > -1)
      return e.split(/\s*\|\s*/).some(this.matchesContext, this);
    let t = e.split("/"), n = this.options.context, i = !this.isOpen && (!n || n.parent.type == this.nodes[0].type), s = -(n ? n.depth + 1 : 0) + (i ? 0 : 1), o = (l, a) => {
      for (; l >= 0; l--) {
        let h = t[l];
        if (h == "") {
          if (l == t.length - 1 || l == 0)
            continue;
          for (; a >= s; a--)
            if (o(l - 1, a))
              return !0;
          return !1;
        } else {
          let c = a > 0 || a == 0 && i ? this.nodes[a].type : n && a >= s ? n.node(a - s).type : null;
          if (!c || c.name != h && c.groups.indexOf(h) == -1)
            return !1;
          a--;
        }
      }
      return !0;
    };
    return o(t.length - 1, this.open);
  }
  textblockFromContext() {
    let e = this.options.context;
    if (e)
      for (let t = e.depth; t >= 0; t--) {
        let n = e.node(t).contentMatchAt(e.indexAfter(t)).defaultType;
        if (n && n.isTextblock && n.defaultAttrs)
          return n;
      }
    for (let t in this.parser.schema.nodes) {
      let n = this.parser.schema.nodes[t];
      if (n.isTextblock && n.defaultAttrs)
        return n;
    }
  }
  addPendingMark(e) {
    let t = ys(e, this.top.pendingMarks);
    t && this.top.stashMarks.push(t), this.top.pendingMarks = e.addToSet(this.top.pendingMarks);
  }
  removePendingMark(e, t) {
    for (let n = this.open; n >= 0; n--) {
      let i = this.nodes[n];
      if (i.pendingMarks.lastIndexOf(e) > -1)
        i.pendingMarks = e.removeFromSet(i.pendingMarks);
      else {
        i.activeMarks = e.removeFromSet(i.activeMarks);
        let o = i.popFromStashMark(e);
        o && i.type && i.type.allowsMarkType(o.type) && (i.activeMarks = o.addToSet(i.activeMarks));
      }
      if (i == t)
        break;
    }
  }
}
function us(r) {
  for (let e = r.firstChild, t = null; e; e = e.nextSibling) {
    let n = e.nodeType == 1 ? e.nodeName.toLowerCase() : null;
    n && Ur.hasOwnProperty(n) && t ? (t.appendChild(e), e = t) : n == "li" ? t = e : n && (t = null);
  }
}
function ps(r, e) {
  return (r.matches || r.msMatchesSelector || r.webkitMatchesSelector || r.mozMatchesSelector).call(r, e);
}
function ms(r) {
  let e = /\s*([\w-]+)\s*:\s*([^;]+)/g, t, n = [];
  for (; t = e.exec(r); )
    n.push(t[1], t[2].trim());
  return n;
}
function Wn(r) {
  let e = {};
  for (let t in r)
    e[t] = r[t];
  return e;
}
function gs(r, e) {
  let t = e.schema.nodes;
  for (let n in t) {
    let i = t[n];
    if (!i.allowsMarkType(r))
      continue;
    let s = [], o = (l) => {
      s.push(l);
      for (let a = 0; a < l.edgeCount; a++) {
        let { type: h, next: c } = l.edge(a);
        if (h == e || s.indexOf(c) < 0 && o(c))
          return !0;
      }
    };
    if (o(i.contentMatch))
      return !0;
  }
}
function ys(r, e) {
  for (let t = 0; t < e.length; t++)
    if (r.eq(e[t]))
      return e[t];
}
class oe {
  constructor(e, t) {
    this.nodes = e, this.marks = t;
  }
  serializeFragment(e, t = {}, n) {
    n || (n = Wt(t).createDocumentFragment());
    let i = n, s = [];
    return e.forEach((o) => {
      if (s.length || o.marks.length) {
        let l = 0, a = 0;
        for (; l < s.length && a < o.marks.length; ) {
          let h = o.marks[a];
          if (!this.marks[h.type.name]) {
            a++;
            continue;
          }
          if (!h.eq(s[l][0]) || h.type.spec.spanning === !1)
            break;
          l++, a++;
        }
        for (; l < s.length; )
          i = s.pop()[1];
        for (; a < o.marks.length; ) {
          let h = o.marks[a++], c = this.serializeMark(h, o.isInline, t);
          c && (s.push([h, i]), i.appendChild(c.dom), i = c.contentDOM || c.dom);
        }
      }
      i.appendChild(this.serializeNodeInner(o, t));
    }), n;
  }
  serializeNodeInner(e, t) {
    let { dom: n, contentDOM: i } = oe.renderSpec(Wt(t), this.nodes[e.type.name](e));
    if (i) {
      if (e.isLeaf)
        throw new RangeError("Content hole not allowed in a leaf node spec");
      this.serializeFragment(e.content, t, i);
    }
    return n;
  }
  serializeNode(e, t = {}) {
    let n = this.serializeNodeInner(e, t);
    for (let i = e.marks.length - 1; i >= 0; i--) {
      let s = this.serializeMark(e.marks[i], e.isInline, t);
      s && ((s.contentDOM || s.dom).appendChild(n), n = s.dom);
    }
    return n;
  }
  serializeMark(e, t, n = {}) {
    let i = this.marks[e.type.name];
    return i && oe.renderSpec(Wt(n), i(e, t));
  }
  static renderSpec(e, t, n = null) {
    if (typeof t == "string")
      return { dom: e.createTextNode(t) };
    if (t.nodeType != null)
      return { dom: t };
    if (t.dom && t.dom.nodeType != null)
      return t;
    let i = t[0], s = i.indexOf(" ");
    s > 0 && (n = i.slice(0, s), i = i.slice(s + 1));
    let o, l = n ? e.createElementNS(n, i) : e.createElement(i), a = t[1], h = 1;
    if (a && typeof a == "object" && a.nodeType == null && !Array.isArray(a)) {
      h = 2;
      for (let c in a)
        if (a[c] != null) {
          let f = c.indexOf(" ");
          f > 0 ? l.setAttributeNS(c.slice(0, f), c.slice(f + 1), a[c]) : l.setAttribute(c, a[c]);
        }
    }
    for (let c = h; c < t.length; c++) {
      let f = t[c];
      if (f === 0) {
        if (c < t.length - 1 || c > h)
          throw new RangeError("Content hole must be the only child of its parent node");
        return { dom: l, contentDOM: l };
      } else {
        let { dom: d, contentDOM: u } = oe.renderSpec(e, f, n);
        if (l.appendChild(d), u) {
          if (o)
            throw new RangeError("Multiple content holes");
          o = u;
        }
      }
    }
    return { dom: l, contentDOM: o };
  }
  static fromSchema(e) {
    return e.cached.domSerializer || (e.cached.domSerializer = new oe(this.nodesFromSchema(e), this.marksFromSchema(e)));
  }
  static nodesFromSchema(e) {
    let t = qn(e.nodes);
    return t.text || (t.text = (n) => n.text), t;
  }
  static marksFromSchema(e) {
    return qn(e.marks);
  }
}
function qn(r) {
  let e = {};
  for (let t in r) {
    let n = r[t].spec.toDOM;
    n && (e[t] = n);
  }
  return e;
}
function Wt(r) {
  return r.document || window.document;
}
const ks = ["em", 0], xs = ["strong", 0], Ss = ["s", 0], bs = ["code", 0], j = new fs({
  nodes: {
    doc: {
      content: "block+"
    },
    paragraph: {
      content: "inline*",
      group: "block",
      toDOM() {
        return ["p", 0];
      },
      parseDOM: [{ tag: "p" }],
      marks: "_"
    },
    text: {
      group: "inline"
    }
  },
  marks: {
    em: {
      parseDOM: [{ tag: "i" }, { tag: "em" }, { style: "font-style=italic" }],
      toDOM() {
        return ks;
      }
    },
    strong: {
      parseDOM: [
        { tag: "strong" },
        {
          tag: "b",
          getAttrs: (r) => r.style.fontWeight != "normal" && null
        },
        {
          style: "font-weight",
          getAttrs: (r) => /^(bold(er)?|[5-9]\d{2,})$/.test(r) && null
        }
      ],
      toDOM() {
        return xs;
      }
    },
    s: {
      parseDOM: [{ tag: "s" }],
      toDOM() {
        return Ss;
      }
    },
    code: {
      parseDOM: [{ tag: "code" }],
      toDOM() {
        return bs;
      }
    }
  }
}), jr = 65535, Gr = Math.pow(2, 16);
function Ms(r, e) {
  return r + e * Gr;
}
function Kn(r) {
  return r & jr;
}
function Cs(r) {
  return (r - (r & jr)) / Gr;
}
const Yr = 1, Xr = 2, pt = 4, Zr = 8;
class ln {
  constructor(e, t, n) {
    this.pos = e, this.delInfo = t, this.recover = n;
  }
  get deleted() {
    return (this.delInfo & Zr) > 0;
  }
  get deletedBefore() {
    return (this.delInfo & (Yr | pt)) > 0;
  }
  get deletedAfter() {
    return (this.delInfo & (Xr | pt)) > 0;
  }
  get deletedAcross() {
    return (this.delInfo & pt) > 0;
  }
}
class $ {
  constructor(e, t = !1) {
    if (this.ranges = e, this.inverted = t, !e.length && $.empty)
      return $.empty;
  }
  recover(e) {
    let t = 0, n = Kn(e);
    if (!this.inverted)
      for (let i = 0; i < n; i++)
        t += this.ranges[i * 3 + 2] - this.ranges[i * 3 + 1];
    return this.ranges[n * 3] + t + Cs(e);
  }
  mapResult(e, t = 1) {
    return this._map(e, t, !1);
  }
  map(e, t = 1) {
    return this._map(e, t, !0);
  }
  _map(e, t, n) {
    let i = 0, s = this.inverted ? 2 : 1, o = this.inverted ? 1 : 2;
    for (let l = 0; l < this.ranges.length; l += 3) {
      let a = this.ranges[l] - (this.inverted ? i : 0);
      if (a > e)
        break;
      let h = this.ranges[l + s], c = this.ranges[l + o], f = a + h;
      if (e <= f) {
        let d = h ? e == a ? -1 : e == f ? 1 : t : t, u = a + i + (d < 0 ? 0 : c);
        if (n)
          return u;
        let p = e == (t < 0 ? a : f) ? null : Ms(l / 3, e - a), m = e == a ? Xr : e == f ? Yr : pt;
        return (t < 0 ? e != a : e != f) && (m |= Zr), new ln(u, m, p);
      }
      i += c - h;
    }
    return n ? e + i : new ln(e + i, 0, null);
  }
  touches(e, t) {
    let n = 0, i = Kn(t), s = this.inverted ? 2 : 1, o = this.inverted ? 1 : 2;
    for (let l = 0; l < this.ranges.length; l += 3) {
      let a = this.ranges[l] - (this.inverted ? n : 0);
      if (a > e)
        break;
      let h = this.ranges[l + s], c = a + h;
      if (e <= c && l == i * 3)
        return !0;
      n += this.ranges[l + o] - h;
    }
    return !1;
  }
  forEach(e) {
    let t = this.inverted ? 2 : 1, n = this.inverted ? 1 : 2;
    for (let i = 0, s = 0; i < this.ranges.length; i += 3) {
      let o = this.ranges[i], l = o - (this.inverted ? s : 0), a = o + (this.inverted ? 0 : s), h = this.ranges[i + t], c = this.ranges[i + n];
      e(l, l + h, a, a + c), s += c - h;
    }
  }
  invert() {
    return new $(this.ranges, !this.inverted);
  }
  toString() {
    return (this.inverted ? "-" : "") + JSON.stringify(this.ranges);
  }
  static offset(e) {
    return e == 0 ? $.empty : new $(e < 0 ? [0, -e, 0] : [0, 0, e]);
  }
}
$.empty = new $([]);
class Je {
  constructor(e = [], t, n = 0, i = e.length) {
    this.maps = e, this.mirror = t, this.from = n, this.to = i;
  }
  slice(e = 0, t = this.maps.length) {
    return new Je(this.maps, this.mirror, e, t);
  }
  copy() {
    return new Je(this.maps.slice(), this.mirror && this.mirror.slice(), this.from, this.to);
  }
  appendMap(e, t) {
    this.to = this.maps.push(e), t != null && this.setMirror(this.maps.length - 1, t);
  }
  appendMapping(e) {
    for (let t = 0, n = this.maps.length; t < e.maps.length; t++) {
      let i = e.getMirror(t);
      this.appendMap(e.maps[t], i != null && i < t ? n + i : void 0);
    }
  }
  getMirror(e) {
    if (this.mirror) {
      for (let t = 0; t < this.mirror.length; t++)
        if (this.mirror[t] == e)
          return this.mirror[t + (t % 2 ? -1 : 1)];
    }
  }
  setMirror(e, t) {
    this.mirror || (this.mirror = []), this.mirror.push(e, t);
  }
  appendMappingInverted(e) {
    for (let t = e.maps.length - 1, n = this.maps.length + e.maps.length; t >= 0; t--) {
      let i = e.getMirror(t);
      this.appendMap(e.maps[t].invert(), i != null && i > t ? n - i - 1 : void 0);
    }
  }
  invert() {
    let e = new Je();
    return e.appendMappingInverted(this), e;
  }
  map(e, t = 1) {
    if (this.mirror)
      return this._map(e, t, !0);
    for (let n = this.from; n < this.to; n++)
      e = this.maps[n].map(e, t);
    return e;
  }
  mapResult(e, t = 1) {
    return this._map(e, t, !1);
  }
  _map(e, t, n) {
    let i = 0;
    for (let s = this.from; s < this.to; s++) {
      let o = this.maps[s], l = o.mapResult(e, t);
      if (l.recover != null) {
        let a = this.getMirror(s);
        if (a != null && a > s && a < this.to) {
          s = a, e = this.maps[a].recover(l.recover);
          continue;
        }
      }
      i |= l.delInfo, e = l.pos;
    }
    return n ? e : new ln(e, i, null);
  }
}
const qt = /* @__PURE__ */ Object.create(null);
class V {
  getMap() {
    return $.empty;
  }
  merge(e) {
    return null;
  }
  static fromJSON(e, t) {
    if (!t || !t.stepType)
      throw new RangeError("Invalid input for Step.fromJSON");
    let n = qt[t.stepType];
    if (!n)
      throw new RangeError(`No step type ${t.stepType} defined`);
    return n.fromJSON(e, t);
  }
  static jsonID(e, t) {
    if (e in qt)
      throw new RangeError("Duplicate use of step JSON ID " + e);
    return qt[e] = t, t.prototype.jsonID = e, t;
  }
}
class N {
  constructor(e, t) {
    this.doc = e, this.failed = t;
  }
  static ok(e) {
    return new N(e, null);
  }
  static fail(e) {
    return new N(null, e);
  }
  static fromReplace(e, t, n, i) {
    try {
      return N.ok(e.replace(t, n, i));
    } catch (s) {
      if (s instanceof kt)
        return N.fail(s.message);
      throw s;
    }
  }
}
function Sn(r, e, t) {
  let n = [];
  for (let i = 0; i < r.childCount; i++) {
    let s = r.child(i);
    s.content.size && (s = s.copy(Sn(s.content, e, s))), s.isInline && (s = e(s, t, i)), n.push(s);
  }
  return g.fromArray(n);
}
class de extends V {
  constructor(e, t, n) {
    super(), this.from = e, this.to = t, this.mark = n;
  }
  apply(e) {
    let t = e.slice(this.from, this.to), n = e.resolve(this.from), i = n.node(n.sharedDepth(this.to)), s = new y(Sn(t.content, (o, l) => !o.isAtom || !l.type.allowsMarkType(this.mark.type) ? o : o.mark(this.mark.addToSet(o.marks)), i), t.openStart, t.openEnd);
    return N.fromReplace(e, this.from, this.to, s);
  }
  invert() {
    return new ee(this.from, this.to, this.mark);
  }
  map(e) {
    let t = e.mapResult(this.from, 1), n = e.mapResult(this.to, -1);
    return t.deleted && n.deleted || t.pos >= n.pos ? null : new de(t.pos, n.pos, this.mark);
  }
  merge(e) {
    return e instanceof de && e.mark.eq(this.mark) && this.from <= e.to && this.to >= e.from ? new de(Math.min(this.from, e.from), Math.max(this.to, e.to), this.mark) : null;
  }
  toJSON() {
    return {
      stepType: "addMark",
      mark: this.mark.toJSON(),
      from: this.from,
      to: this.to
    };
  }
  static fromJSON(e, t) {
    if (typeof t.from != "number" || typeof t.to != "number")
      throw new RangeError("Invalid input for AddMarkStep.fromJSON");
    return new de(t.from, t.to, e.markFromJSON(t.mark));
  }
}
V.jsonID("addMark", de);
class ee extends V {
  constructor(e, t, n) {
    super(), this.from = e, this.to = t, this.mark = n;
  }
  apply(e) {
    let t = e.slice(this.from, this.to), n = new y(Sn(t.content, (i) => i.mark(this.mark.removeFromSet(i.marks)), e), t.openStart, t.openEnd);
    return N.fromReplace(e, this.from, this.to, n);
  }
  invert() {
    return new de(this.from, this.to, this.mark);
  }
  map(e) {
    let t = e.mapResult(this.from, 1), n = e.mapResult(this.to, -1);
    return t.deleted && n.deleted || t.pos >= n.pos ? null : new ee(t.pos, n.pos, this.mark);
  }
  merge(e) {
    return e instanceof ee && e.mark.eq(this.mark) && this.from <= e.to && this.to >= e.from ? new ee(Math.min(this.from, e.from), Math.max(this.to, e.to), this.mark) : null;
  }
  toJSON() {
    return {
      stepType: "removeMark",
      mark: this.mark.toJSON(),
      from: this.from,
      to: this.to
    };
  }
  static fromJSON(e, t) {
    if (typeof t.from != "number" || typeof t.to != "number")
      throw new RangeError("Invalid input for RemoveMarkStep.fromJSON");
    return new ee(t.from, t.to, e.markFromJSON(t.mark));
  }
}
V.jsonID("removeMark", ee);
class ue extends V {
  constructor(e, t) {
    super(), this.pos = e, this.mark = t;
  }
  apply(e) {
    let t = e.nodeAt(this.pos);
    if (!t)
      return N.fail("No node at mark step's position");
    let n = t.type.create(t.attrs, null, this.mark.addToSet(t.marks));
    return N.fromReplace(e, this.pos, this.pos + 1, new y(g.from(n), 0, t.isLeaf ? 0 : 1));
  }
  invert(e) {
    let t = e.nodeAt(this.pos);
    if (t) {
      let n = this.mark.addToSet(t.marks);
      if (n.length == t.marks.length) {
        for (let i = 0; i < t.marks.length; i++)
          if (!t.marks[i].isInSet(n))
            return new ue(this.pos, t.marks[i]);
        return new ue(this.pos, this.mark);
      }
    }
    return new Ke(this.pos, this.mark);
  }
  map(e) {
    let t = e.mapResult(this.pos, 1);
    return t.deletedAfter ? null : new ue(t.pos, this.mark);
  }
  toJSON() {
    return { stepType: "addNodeMark", pos: this.pos, mark: this.mark.toJSON() };
  }
  static fromJSON(e, t) {
    if (typeof t.pos != "number")
      throw new RangeError("Invalid input for AddNodeMarkStep.fromJSON");
    return new ue(t.pos, e.markFromJSON(t.mark));
  }
}
V.jsonID("addNodeMark", ue);
class Ke extends V {
  constructor(e, t) {
    super(), this.pos = e, this.mark = t;
  }
  apply(e) {
    let t = e.nodeAt(this.pos);
    if (!t)
      return N.fail("No node at mark step's position");
    let n = t.type.create(t.attrs, null, this.mark.removeFromSet(t.marks));
    return N.fromReplace(e, this.pos, this.pos + 1, new y(g.from(n), 0, t.isLeaf ? 0 : 1));
  }
  invert(e) {
    let t = e.nodeAt(this.pos);
    return !t || !this.mark.isInSet(t.marks) ? this : new ue(this.pos, this.mark);
  }
  map(e) {
    let t = e.mapResult(this.pos, 1);
    return t.deletedAfter ? null : new Ke(t.pos, this.mark);
  }
  toJSON() {
    return { stepType: "removeNodeMark", pos: this.pos, mark: this.mark.toJSON() };
  }
  static fromJSON(e, t) {
    if (typeof t.pos != "number")
      throw new RangeError("Invalid input for RemoveNodeMarkStep.fromJSON");
    return new Ke(t.pos, e.markFromJSON(t.mark));
  }
}
V.jsonID("removeNodeMark", Ke);
class I extends V {
  constructor(e, t, n, i = !1) {
    super(), this.from = e, this.to = t, this.slice = n, this.structure = i;
  }
  apply(e) {
    return this.structure && an(e, this.from, this.to) ? N.fail("Structure replace would overwrite content") : N.fromReplace(e, this.from, this.to, this.slice);
  }
  getMap() {
    return new $([this.from, this.to - this.from, this.slice.size]);
  }
  invert(e) {
    return new I(this.from, this.from + this.slice.size, e.slice(this.from, this.to));
  }
  map(e) {
    let t = e.mapResult(this.from, 1), n = e.mapResult(this.to, -1);
    return t.deletedAcross && n.deletedAcross ? null : new I(t.pos, Math.max(t.pos, n.pos), this.slice);
  }
  merge(e) {
    if (!(e instanceof I) || e.structure || this.structure)
      return null;
    if (this.from + this.slice.size == e.from && !this.slice.openEnd && !e.slice.openStart) {
      let t = this.slice.size + e.slice.size == 0 ? y.empty : new y(this.slice.content.append(e.slice.content), this.slice.openStart, e.slice.openEnd);
      return new I(this.from, this.to + (e.to - e.from), t, this.structure);
    } else if (e.to == this.from && !this.slice.openStart && !e.slice.openEnd) {
      let t = this.slice.size + e.slice.size == 0 ? y.empty : new y(e.slice.content.append(this.slice.content), e.slice.openStart, this.slice.openEnd);
      return new I(e.from, this.to, t, this.structure);
    } else
      return null;
  }
  toJSON() {
    let e = { stepType: "replace", from: this.from, to: this.to };
    return this.slice.size && (e.slice = this.slice.toJSON()), this.structure && (e.structure = !0), e;
  }
  static fromJSON(e, t) {
    if (typeof t.from != "number" || typeof t.to != "number")
      throw new RangeError("Invalid input for ReplaceStep.fromJSON");
    return new I(t.from, t.to, y.fromJSON(e, t.slice), !!t.structure);
  }
}
V.jsonID("replace", I);
class W extends V {
  constructor(e, t, n, i, s, o, l = !1) {
    super(), this.from = e, this.to = t, this.gapFrom = n, this.gapTo = i, this.slice = s, this.insert = o, this.structure = l;
  }
  apply(e) {
    if (this.structure && (an(e, this.from, this.gapFrom) || an(e, this.gapTo, this.to)))
      return N.fail("Structure gap-replace would overwrite content");
    let t = e.slice(this.gapFrom, this.gapTo);
    if (t.openStart || t.openEnd)
      return N.fail("Gap is not a flat range");
    let n = this.slice.insertAt(this.insert, t.content);
    return n ? N.fromReplace(e, this.from, this.to, n) : N.fail("Content does not fit in gap");
  }
  getMap() {
    return new $([
      this.from,
      this.gapFrom - this.from,
      this.insert,
      this.gapTo,
      this.to - this.gapTo,
      this.slice.size - this.insert
    ]);
  }
  invert(e) {
    let t = this.gapTo - this.gapFrom;
    return new W(this.from, this.from + this.slice.size + t, this.from + this.insert, this.from + this.insert + t, e.slice(this.from, this.to).removeBetween(this.gapFrom - this.from, this.gapTo - this.from), this.gapFrom - this.from, this.structure);
  }
  map(e) {
    let t = e.mapResult(this.from, 1), n = e.mapResult(this.to, -1), i = e.map(this.gapFrom, -1), s = e.map(this.gapTo, 1);
    return t.deletedAcross && n.deletedAcross || i < t.pos || s > n.pos ? null : new W(t.pos, n.pos, i, s, this.slice, this.insert, this.structure);
  }
  toJSON() {
    let e = {
      stepType: "replaceAround",
      from: this.from,
      to: this.to,
      gapFrom: this.gapFrom,
      gapTo: this.gapTo,
      insert: this.insert
    };
    return this.slice.size && (e.slice = this.slice.toJSON()), this.structure && (e.structure = !0), e;
  }
  static fromJSON(e, t) {
    if (typeof t.from != "number" || typeof t.to != "number" || typeof t.gapFrom != "number" || typeof t.gapTo != "number" || typeof t.insert != "number")
      throw new RangeError("Invalid input for ReplaceAroundStep.fromJSON");
    return new W(t.from, t.to, t.gapFrom, t.gapTo, y.fromJSON(e, t.slice), t.insert, !!t.structure);
  }
}
V.jsonID("replaceAround", W);
function an(r, e, t) {
  let n = r.resolve(e), i = t - e, s = n.depth;
  for (; i > 0 && s > 0 && n.indexAfter(s) == n.node(s).childCount; )
    s--, i--;
  if (i > 0) {
    let o = n.node(s).maybeChild(n.indexAfter(s));
    for (; i > 0; ) {
      if (!o || o.isLeaf)
        return !0;
      o = o.firstChild, i--;
    }
  }
  return !1;
}
function Os(r, e, t, n) {
  let i = [], s = [], o, l;
  r.doc.nodesBetween(e, t, (a, h, c) => {
    if (!a.isInline)
      return;
    let f = a.marks;
    if (!n.isInSet(f) && c.type.allowsMarkType(n.type)) {
      let d = Math.max(h, e), u = Math.min(h + a.nodeSize, t), p = n.addToSet(f);
      for (let m = 0; m < f.length; m++)
        f[m].isInSet(p) || (o && o.to == d && o.mark.eq(f[m]) ? o.to = u : i.push(o = new ee(d, u, f[m])));
      l && l.to == d ? l.to = u : s.push(l = new de(d, u, n));
    }
  }), i.forEach((a) => r.step(a)), s.forEach((a) => r.step(a));
}
function Ns(r, e, t, n) {
  let i = [], s = 0;
  r.doc.nodesBetween(e, t, (o, l) => {
    if (!o.isInline)
      return;
    s++;
    let a = null;
    if (n instanceof Et) {
      let h = o.marks, c;
      for (; c = n.isInSet(h); )
        (a || (a = [])).push(c), h = c.removeFromSet(h);
    } else
      n ? n.isInSet(o.marks) && (a = [n]) : a = o.marks;
    if (a && a.length) {
      let h = Math.min(l + o.nodeSize, t);
      for (let c = 0; c < a.length; c++) {
        let f = a[c], d;
        for (let u = 0; u < i.length; u++) {
          let p = i[u];
          p.step == s - 1 && f.eq(i[u].style) && (d = p);
        }
        d ? (d.to = h, d.step = s) : i.push({ style: f, from: Math.max(l, e), to: h, step: s });
      }
    }
  }), i.forEach((o) => r.step(new ee(o.from, o.to, o.style)));
}
function ws(r, e, t, n = t.contentMatch) {
  let i = r.doc.nodeAt(e), s = [], o = e + 1;
  for (let l = 0; l < i.childCount; l++) {
    let a = i.child(l), h = o + a.nodeSize, c = n.matchType(a.type);
    if (!c)
      s.push(new I(o, h, y.empty));
    else {
      n = c;
      for (let f = 0; f < a.marks.length; f++)
        t.allowsMarkType(a.marks[f].type) || r.step(new ee(o, h, a.marks[f]));
    }
    o = h;
  }
  if (!n.validEnd) {
    let l = n.fillBefore(g.empty, !0);
    r.replace(o, o, new y(l, 0, 0));
  }
  for (let l = s.length - 1; l >= 0; l--)
    r.step(s[l]);
}
function Ts(r, e, t) {
  return (e == 0 || r.canReplace(e, r.childCount)) && (t == r.childCount || r.canReplace(0, t));
}
function bn(r) {
  let t = r.parent.content.cutByIndex(r.startIndex, r.endIndex);
  for (let n = r.depth; ; --n) {
    let i = r.$from.node(n), s = r.$from.index(n), o = r.$to.indexAfter(n);
    if (n < r.depth && i.canReplace(s, o, t))
      return n;
    if (n == 0 || i.type.spec.isolating || !Ts(i, s, o))
      break;
  }
  return null;
}
function Ds(r, e, t) {
  let { $from: n, $to: i, depth: s } = e, o = n.before(s + 1), l = i.after(s + 1), a = o, h = l, c = g.empty, f = 0;
  for (let p = s, m = !1; p > t; p--)
    m || n.index(p) > 0 ? (m = !0, c = g.from(n.node(p).copy(c)), f++) : a--;
  let d = g.empty, u = 0;
  for (let p = s, m = !1; p > t; p--)
    m || i.after(p + 1) < i.end(p) ? (m = !0, d = g.from(i.node(p).copy(d)), u++) : h++;
  r.step(new W(a, h, o, l, new y(c.append(d), f, u), c.size - f, !0));
}
function Es(r, e, t) {
  let n = g.empty;
  for (let o = t.length - 1; o >= 0; o--) {
    if (n.size) {
      let l = t[o].type.contentMatch.matchFragment(n);
      if (!l || !l.validEnd)
        throw new RangeError("Wrapper type given to Transform.wrap does not form valid content of its parent wrapper");
    }
    n = g.from(t[o].type.create(t[o].attrs, n));
  }
  let i = e.start, s = e.end;
  r.step(new W(i, s, i, s, new y(n, 0, 0), t.length, !0));
}
function As(r, e, t, n, i) {
  if (!n.isTextblock)
    throw new RangeError("Type given to setBlockType should be a textblock");
  let s = r.steps.length;
  r.doc.nodesBetween(e, t, (o, l) => {
    if (o.isTextblock && !o.hasMarkup(n, i) && Is(r.doc, r.mapping.slice(s).map(l), n)) {
      r.clearIncompatible(r.mapping.slice(s).map(l, 1), n);
      let a = r.mapping.slice(s), h = a.map(l, 1), c = a.map(l + o.nodeSize, 1);
      return r.step(new W(h, c, h + 1, c - 1, new y(g.from(n.create(i, null, o.marks)), 0, 0), 1, !0)), !1;
    }
  });
}
function Is(r, e, t) {
  let n = r.resolve(e), i = n.index();
  return n.parent.canReplaceWith(i, i + 1, t);
}
function Rs(r, e, t, n, i) {
  let s = r.doc.nodeAt(e);
  if (!s)
    throw new RangeError("No node at given position");
  t || (t = s.type);
  let o = t.create(n, null, i || s.marks);
  if (s.isLeaf)
    return r.replaceWith(e, e + s.nodeSize, o);
  if (!t.validContent(s.content))
    throw new RangeError("Invalid content for node type " + t.name);
  r.step(new W(e, e + s.nodeSize, e + 1, e + s.nodeSize - 1, new y(g.from(o), 0, 0), 1, !0));
}
function mt(r, e, t = 1, n) {
  let i = r.resolve(e), s = i.depth - t, o = n && n[n.length - 1] || i.parent;
  if (s < 0 || i.parent.type.spec.isolating || !i.parent.canReplace(i.index(), i.parent.childCount) || !o.type.validContent(i.parent.content.cutByIndex(i.index(), i.parent.childCount)))
    return !1;
  for (let h = i.depth - 1, c = t - 2; h > s; h--, c--) {
    let f = i.node(h), d = i.index(h);
    if (f.type.spec.isolating)
      return !1;
    let u = f.content.cutByIndex(d, f.childCount), p = n && n[c] || f;
    if (p != f && (u = u.replaceChild(0, p.type.create(p.attrs))), !f.canReplace(d + 1, f.childCount) || !p.type.validContent(u))
      return !1;
  }
  let l = i.indexAfter(s), a = n && n[0];
  return i.node(s).canReplaceWith(l, l, a ? a.type : i.node(s + 1).type);
}
function Ps(r, e, t = 1, n) {
  let i = r.doc.resolve(e), s = g.empty, o = g.empty;
  for (let l = i.depth, a = i.depth - t, h = t - 1; l > a; l--, h--) {
    s = g.from(i.node(l).copy(s));
    let c = n && n[h];
    o = g.from(c ? c.type.create(c.attrs, o) : i.node(l).copy(o));
  }
  r.step(new I(e, e, new y(s.append(o), t, t), !0));
}
function Qr(r, e) {
  let t = r.resolve(e), n = t.index();
  return zs(t.nodeBefore, t.nodeAfter) && t.parent.canReplace(n, n + 1);
}
function zs(r, e) {
  return !!(r && e && !r.isLeaf && r.canAppend(e));
}
function Bs(r, e, t) {
  let n = new I(e - t, e + t, y.empty, !0);
  r.step(n);
}
function vs(r, e, t) {
  let n = r.resolve(e);
  if (n.parent.canReplaceWith(n.index(), n.index(), t))
    return e;
  if (n.parentOffset == 0)
    for (let i = n.depth - 1; i >= 0; i--) {
      let s = n.index(i);
      if (n.node(i).canReplaceWith(s, s, t))
        return n.before(i + 1);
      if (s > 0)
        return null;
    }
  if (n.parentOffset == n.parent.content.size)
    for (let i = n.depth - 1; i >= 0; i--) {
      let s = n.indexAfter(i);
      if (n.node(i).canReplaceWith(s, s, t))
        return n.after(i + 1);
      if (s < n.node(i).childCount)
        return null;
    }
  return null;
}
function Fs(r, e, t) {
  let n = r.resolve(e);
  if (!t.content.size)
    return e;
  let i = t.content;
  for (let s = 0; s < t.openStart; s++)
    i = i.firstChild.content;
  for (let s = 1; s <= (t.openStart == 0 && t.size ? 2 : 1); s++)
    for (let o = n.depth; o >= 0; o--) {
      let l = o == n.depth ? 0 : n.pos <= (n.start(o + 1) + n.end(o + 1)) / 2 ? -1 : 1, a = n.index(o) + (l > 0 ? 1 : 0), h = n.node(o), c = !1;
      if (s == 1)
        c = h.canReplace(a, a, i);
      else {
        let f = h.contentMatchAt(a).findWrapping(i.firstChild.type);
        c = f && h.canReplaceWith(a, a, f[0]);
      }
      if (c)
        return l == 0 ? n.pos : l < 0 ? n.before(o + 1) : n.after(o + 1);
    }
  return null;
}
function Mn(r, e, t = e, n = y.empty) {
  if (e == t && !n.size)
    return null;
  let i = r.resolve(e), s = r.resolve(t);
  return _r(i, s, n) ? new I(e, t, n) : new Vs(i, s, n).fit();
}
function _r(r, e, t) {
  return !t.openStart && !t.openEnd && r.start() == e.start() && r.parent.canReplace(r.index(), e.index(), t.content);
}
class Vs {
  constructor(e, t, n) {
    this.$from = e, this.$to = t, this.unplaced = n, this.frontier = [], this.placed = g.empty;
    for (let i = 0; i <= e.depth; i++) {
      let s = e.node(i);
      this.frontier.push({
        type: s.type,
        match: s.contentMatchAt(e.indexAfter(i))
      });
    }
    for (let i = e.depth; i > 0; i--)
      this.placed = g.from(e.node(i).copy(this.placed));
  }
  get depth() {
    return this.frontier.length - 1;
  }
  fit() {
    for (; this.unplaced.size; ) {
      let h = this.findFittable();
      h ? this.placeNodes(h) : this.openMore() || this.dropNode();
    }
    let e = this.mustMoveInline(), t = this.placed.size - this.depth - this.$from.depth, n = this.$from, i = this.close(e < 0 ? this.$to : n.doc.resolve(e));
    if (!i)
      return null;
    let s = this.placed, o = n.depth, l = i.depth;
    for (; o && l && s.childCount == 1; )
      s = s.firstChild.content, o--, l--;
    let a = new y(s, o, l);
    return e > -1 ? new W(n.pos, e, this.$to.pos, this.$to.end(), a, t) : a.size || n.pos != this.$to.pos ? new I(n.pos, i.pos, a) : null;
  }
  findFittable() {
    for (let e = 1; e <= 2; e++)
      for (let t = this.unplaced.openStart; t >= 0; t--) {
        let n, i = null;
        t ? (i = Kt(this.unplaced.content, t - 1).firstChild, n = i.content) : n = this.unplaced.content;
        let s = n.firstChild;
        for (let o = this.depth; o >= 0; o--) {
          let { type: l, match: a } = this.frontier[o], h, c = null;
          if (e == 1 && (s ? a.matchType(s.type) || (c = a.fillBefore(g.from(s), !1)) : i && l.compatibleContent(i.type)))
            return { sliceDepth: t, frontierDepth: o, parent: i, inject: c };
          if (e == 2 && s && (h = a.findWrapping(s.type)))
            return { sliceDepth: t, frontierDepth: o, parent: i, wrap: h };
          if (i && a.matchType(i.type))
            break;
        }
      }
  }
  openMore() {
    let { content: e, openStart: t, openEnd: n } = this.unplaced, i = Kt(e, t);
    return !i.childCount || i.firstChild.isLeaf ? !1 : (this.unplaced = new y(e, t + 1, Math.max(n, i.size + t >= e.size - n ? t + 1 : 0)), !0);
  }
  dropNode() {
    let { content: e, openStart: t, openEnd: n } = this.unplaced, i = Kt(e, t);
    if (i.childCount <= 1 && t > 0) {
      let s = e.size - t <= t + i.size;
      this.unplaced = new y(Xe(e, t - 1, 1), t - 1, s ? t - 1 : n);
    } else
      this.unplaced = new y(Xe(e, t, 1), t, n);
  }
  placeNodes({ sliceDepth: e, frontierDepth: t, parent: n, inject: i, wrap: s }) {
    for (; this.depth > t; )
      this.closeFrontierNode();
    if (s)
      for (let m = 0; m < s.length; m++)
        this.openFrontierNode(s[m]);
    let o = this.unplaced, l = n ? n.content : o.content, a = o.openStart - e, h = 0, c = [], { match: f, type: d } = this.frontier[t];
    if (i) {
      for (let m = 0; m < i.childCount; m++)
        c.push(i.child(m));
      f = f.matchFragment(i);
    }
    let u = l.size + e - (o.content.size - o.openEnd);
    for (; h < l.childCount; ) {
      let m = l.child(h), k = f.matchType(m.type);
      if (!k)
        break;
      h++, (h > 1 || a == 0 || m.content.size) && (f = k, c.push(ei(m.mark(d.allowedMarks(m.marks)), h == 1 ? a : 0, h == l.childCount ? u : -1)));
    }
    let p = h == l.childCount;
    p || (u = -1), this.placed = Ze(this.placed, t, g.from(c)), this.frontier[t].match = f, p && u < 0 && n && n.type == this.frontier[this.depth].type && this.frontier.length > 1 && this.closeFrontierNode();
    for (let m = 0, k = l; m < u; m++) {
      let S = k.lastChild;
      this.frontier.push({ type: S.type, match: S.contentMatchAt(S.childCount) }), k = S.content;
    }
    this.unplaced = p ? e == 0 ? y.empty : new y(Xe(o.content, e - 1, 1), e - 1, u < 0 ? o.openEnd : e - 1) : new y(Xe(o.content, e, h), o.openStart, o.openEnd);
  }
  mustMoveInline() {
    if (!this.$to.parent.isTextblock)
      return -1;
    let e = this.frontier[this.depth], t;
    if (!e.type.isTextblock || !Ht(this.$to, this.$to.depth, e.type, e.match, !1) || this.$to.depth == this.depth && (t = this.findCloseLevel(this.$to)) && t.depth == this.depth)
      return -1;
    let { depth: n } = this.$to, i = this.$to.after(n);
    for (; n > 1 && i == this.$to.end(--n); )
      ++i;
    return i;
  }
  findCloseLevel(e) {
    e:
      for (let t = Math.min(this.depth, e.depth); t >= 0; t--) {
        let { match: n, type: i } = this.frontier[t], s = t < e.depth && e.end(t + 1) == e.pos + (e.depth - (t + 1)), o = Ht(e, t, i, n, s);
        if (!!o) {
          for (let l = t - 1; l >= 0; l--) {
            let { match: a, type: h } = this.frontier[l], c = Ht(e, l, h, a, !0);
            if (!c || c.childCount)
              continue e;
          }
          return { depth: t, fit: o, move: s ? e.doc.resolve(e.after(t + 1)) : e };
        }
      }
  }
  close(e) {
    let t = this.findCloseLevel(e);
    if (!t)
      return null;
    for (; this.depth > t.depth; )
      this.closeFrontierNode();
    t.fit.childCount && (this.placed = Ze(this.placed, t.depth, t.fit)), e = t.move;
    for (let n = t.depth + 1; n <= e.depth; n++) {
      let i = e.node(n), s = i.type.contentMatch.fillBefore(i.content, !0, e.index(n));
      this.openFrontierNode(i.type, i.attrs, s);
    }
    return e;
  }
  openFrontierNode(e, t = null, n) {
    let i = this.frontier[this.depth];
    i.match = i.match.matchType(e), this.placed = Ze(this.placed, this.depth, g.from(e.create(t, n))), this.frontier.push({ type: e, match: e.contentMatch });
  }
  closeFrontierNode() {
    let t = this.frontier.pop().match.fillBefore(g.empty, !0);
    t.childCount && (this.placed = Ze(this.placed, this.frontier.length, t));
  }
}
function Xe(r, e, t) {
  return e == 0 ? r.cutByIndex(t, r.childCount) : r.replaceChild(0, r.firstChild.copy(Xe(r.firstChild.content, e - 1, t)));
}
function Ze(r, e, t) {
  return e == 0 ? r.append(t) : r.replaceChild(r.childCount - 1, r.lastChild.copy(Ze(r.lastChild.content, e - 1, t)));
}
function Kt(r, e) {
  for (let t = 0; t < e; t++)
    r = r.firstChild.content;
  return r;
}
function ei(r, e, t) {
  if (e <= 0)
    return r;
  let n = r.content;
  return e > 1 && (n = n.replaceChild(0, ei(n.firstChild, e - 1, n.childCount == 1 ? t - 1 : 0))), e > 0 && (n = r.type.contentMatch.fillBefore(n).append(n), t <= 0 && (n = n.append(r.type.contentMatch.matchFragment(n).fillBefore(g.empty, !0)))), r.copy(n);
}
function Ht(r, e, t, n, i) {
  let s = r.node(e), o = i ? r.indexAfter(e) : r.index(e);
  if (o == s.childCount && !t.compatibleContent(s.type))
    return null;
  let l = n.fillBefore(s.content, !0, o);
  return l && !Ls(t, s.content, o) ? l : null;
}
function Ls(r, e, t) {
  for (let n = t; n < e.childCount; n++)
    if (!r.allowsMarks(e.child(n).marks))
      return !0;
  return !1;
}
function Js(r) {
  return r.spec.defining || r.spec.definingForContent;
}
function Ws(r, e, t, n) {
  if (!n.size)
    return r.deleteRange(e, t);
  let i = r.doc.resolve(e), s = r.doc.resolve(t);
  if (_r(i, s, n))
    return r.step(new I(e, t, n));
  let o = ni(i, r.doc.resolve(t));
  o[o.length - 1] == 0 && o.pop();
  let l = -(i.depth + 1);
  o.unshift(l);
  for (let d = i.depth, u = i.pos - 1; d > 0; d--, u--) {
    let p = i.node(d).type.spec;
    if (p.defining || p.definingAsContext || p.isolating)
      break;
    o.indexOf(d) > -1 ? l = d : i.before(d) == u && o.splice(1, 0, -d);
  }
  let a = o.indexOf(l), h = [], c = n.openStart;
  for (let d = n.content, u = 0; ; u++) {
    let p = d.firstChild;
    if (h.push(p), u == n.openStart)
      break;
    d = p.content;
  }
  for (let d = c - 1; d >= 0; d--) {
    let u = h[d].type, p = Js(u);
    if (p && i.node(a).type != u)
      c = d;
    else if (p || !u.isTextblock)
      break;
  }
  for (let d = n.openStart; d >= 0; d--) {
    let u = (d + c + 1) % (n.openStart + 1), p = h[u];
    if (!!p)
      for (let m = 0; m < o.length; m++) {
        let k = o[(m + a) % o.length], S = !0;
        k < 0 && (S = !1, k = -k);
        let L = i.node(k - 1), B = i.index(k - 1);
        if (L.canReplaceWith(B, B, p.type, p.marks))
          return r.replace(i.before(k), S ? s.after(k) : t, new y(ti(n.content, 0, n.openStart, u), u, n.openEnd));
      }
  }
  let f = r.steps.length;
  for (let d = o.length - 1; d >= 0 && (r.replace(e, t, n), !(r.steps.length > f)); d--) {
    let u = o[d];
    u < 0 || (e = i.before(u), t = s.after(u));
  }
}
function ti(r, e, t, n, i) {
  if (e < t) {
    let s = r.firstChild;
    r = r.replaceChild(0, s.copy(ti(s.content, e + 1, t, n, s)));
  }
  if (e > n) {
    let s = i.contentMatchAt(0), o = s.fillBefore(r).append(r);
    r = o.append(s.matchFragment(o).fillBefore(g.empty, !0));
  }
  return r;
}
function qs(r, e, t, n) {
  if (!n.isInline && e == t && r.doc.resolve(e).parent.content.size) {
    let i = vs(r.doc, e, n.type);
    i != null && (e = t = i);
  }
  r.replaceRange(e, t, new y(g.from(n), 0, 0));
}
function Ks(r, e, t) {
  let n = r.doc.resolve(e), i = r.doc.resolve(t), s = ni(n, i);
  for (let o = 0; o < s.length; o++) {
    let l = s[o], a = o == s.length - 1;
    if (a && l == 0 || n.node(l).type.contentMatch.validEnd)
      return r.delete(n.start(l), i.end(l));
    if (l > 0 && (a || n.node(l - 1).canReplace(n.index(l - 1), i.indexAfter(l - 1))))
      return r.delete(n.before(l), i.after(l));
  }
  for (let o = 1; o <= n.depth && o <= i.depth; o++)
    if (e - n.start(o) == n.depth - o && t > n.end(o) && i.end(o) - t != i.depth - o)
      return r.delete(n.before(o), t);
  r.delete(e, t);
}
function ni(r, e) {
  let t = [], n = Math.min(r.depth, e.depth);
  for (let i = n; i >= 0; i--) {
    let s = r.start(i);
    if (s < r.pos - (r.depth - i) || e.end(i) > e.pos + (e.depth - i) || r.node(i).type.spec.isolating || e.node(i).type.spec.isolating)
      break;
    (s == e.start(i) || i == r.depth && i == e.depth && r.parent.inlineContent && e.parent.inlineContent && i && e.start(i - 1) == s - 1) && t.push(i);
  }
  return t;
}
class We extends V {
  constructor(e, t, n) {
    super(), this.pos = e, this.attr = t, this.value = n;
  }
  apply(e) {
    let t = e.nodeAt(this.pos);
    if (!t)
      return N.fail("No node at attribute step's position");
    let n = /* @__PURE__ */ Object.create(null);
    for (let s in t.attrs)
      n[s] = t.attrs[s];
    n[this.attr] = this.value;
    let i = t.type.create(n, null, t.marks);
    return N.fromReplace(e, this.pos, this.pos + 1, new y(g.from(i), 0, t.isLeaf ? 0 : 1));
  }
  getMap() {
    return $.empty;
  }
  invert(e) {
    return new We(this.pos, this.attr, e.nodeAt(this.pos).attrs[this.attr]);
  }
  map(e) {
    let t = e.mapResult(this.pos, 1);
    return t.deletedAfter ? null : new We(t.pos, this.attr, this.value);
  }
  toJSON() {
    return { stepType: "attr", pos: this.pos, attr: this.attr, value: this.value };
  }
  static fromJSON(e, t) {
    if (typeof t.pos != "number" || typeof t.attr != "string")
      throw new RangeError("Invalid input for AttrStep.fromJSON");
    return new We(t.pos, t.attr, t.value);
  }
}
V.jsonID("attr", We);
let He = class extends Error {
};
He = function r(e) {
  let t = Error.call(this, e);
  return t.__proto__ = r.prototype, t;
};
He.prototype = Object.create(Error.prototype);
He.prototype.constructor = He;
He.prototype.name = "TransformError";
class Hs {
  constructor(e) {
    this.doc = e, this.steps = [], this.docs = [], this.mapping = new Je();
  }
  get before() {
    return this.docs.length ? this.docs[0] : this.doc;
  }
  step(e) {
    let t = this.maybeStep(e);
    if (t.failed)
      throw new He(t.failed);
    return this;
  }
  maybeStep(e) {
    let t = e.apply(this.doc);
    return t.failed || this.addStep(e, t.doc), t;
  }
  get docChanged() {
    return this.steps.length > 0;
  }
  addStep(e, t) {
    this.docs.push(this.doc), this.steps.push(e), this.mapping.appendMap(e.getMap()), this.doc = t;
  }
  replace(e, t = e, n = y.empty) {
    let i = Mn(this.doc, e, t, n);
    return i && this.step(i), this;
  }
  replaceWith(e, t, n) {
    return this.replace(e, t, new y(g.from(n), 0, 0));
  }
  delete(e, t) {
    return this.replace(e, t, y.empty);
  }
  insert(e, t) {
    return this.replaceWith(e, e, t);
  }
  replaceRange(e, t, n) {
    return Ws(this, e, t, n), this;
  }
  replaceRangeWith(e, t, n) {
    return qs(this, e, t, n), this;
  }
  deleteRange(e, t) {
    return Ks(this, e, t), this;
  }
  lift(e, t) {
    return Ds(this, e, t), this;
  }
  join(e, t = 1) {
    return Bs(this, e, t), this;
  }
  wrap(e, t) {
    return Es(this, e, t), this;
  }
  setBlockType(e, t = e, n, i = null) {
    return As(this, e, t, n, i), this;
  }
  setNodeMarkup(e, t, n = null, i = []) {
    return Rs(this, e, t, n, i), this;
  }
  setNodeAttribute(e, t, n) {
    return this.step(new We(e, t, n)), this;
  }
  addNodeMark(e, t) {
    return this.step(new ue(e, t)), this;
  }
  removeNodeMark(e, t) {
    if (!(t instanceof M)) {
      let n = this.doc.nodeAt(e);
      if (!n)
        throw new RangeError("No node at position " + e);
      if (t = t.isInSet(n.marks), !t)
        return this;
    }
    return this.step(new Ke(e, t)), this;
  }
  split(e, t = 1, n) {
    return Ps(this, e, t, n), this;
  }
  addMark(e, t, n) {
    return Os(this, e, t, n), this;
  }
  removeMark(e, t, n) {
    return Ns(this, e, t, n), this;
  }
  clearIncompatible(e, t, n) {
    return ws(this, e, t, n), this;
  }
}
const $t = /* @__PURE__ */ Object.create(null);
class C {
  constructor(e, t, n) {
    this.$anchor = e, this.$head = t, this.ranges = n || [new $s(e.min(t), e.max(t))];
  }
  get anchor() {
    return this.$anchor.pos;
  }
  get head() {
    return this.$head.pos;
  }
  get from() {
    return this.$from.pos;
  }
  get to() {
    return this.$to.pos;
  }
  get $from() {
    return this.ranges[0].$from;
  }
  get $to() {
    return this.ranges[0].$to;
  }
  get empty() {
    let e = this.ranges;
    for (let t = 0; t < e.length; t++)
      if (e[t].$from.pos != e[t].$to.pos)
        return !1;
    return !0;
  }
  content() {
    return this.$from.doc.slice(this.from, this.to, !0);
  }
  replace(e, t = y.empty) {
    let n = t.content.lastChild, i = null;
    for (let l = 0; l < t.openEnd; l++)
      i = n, n = n.lastChild;
    let s = e.steps.length, o = this.ranges;
    for (let l = 0; l < o.length; l++) {
      let { $from: a, $to: h } = o[l], c = e.mapping.slice(s);
      e.replaceRange(c.map(a.pos), c.map(h.pos), l ? y.empty : t), l == 0 && Un(e, s, (n ? n.isInline : i && i.isTextblock) ? -1 : 1);
    }
  }
  replaceWith(e, t) {
    let n = e.steps.length, i = this.ranges;
    for (let s = 0; s < i.length; s++) {
      let { $from: o, $to: l } = i[s], a = e.mapping.slice(n), h = a.map(o.pos), c = a.map(l.pos);
      s ? e.deleteRange(h, c) : (e.replaceRangeWith(h, c, t), Un(e, n, t.isInline ? -1 : 1));
    }
  }
  static findFrom(e, t, n = !1) {
    let i = e.parent.inlineContent ? new O(e) : ze(e.node(0), e.parent, e.pos, e.index(), t, n);
    if (i)
      return i;
    for (let s = e.depth - 1; s >= 0; s--) {
      let o = t < 0 ? ze(e.node(0), e.node(s), e.before(s + 1), e.index(s), t, n) : ze(e.node(0), e.node(s), e.after(s + 1), e.index(s) + 1, t, n);
      if (o)
        return o;
    }
    return null;
  }
  static near(e, t = 1) {
    return this.findFrom(e, t) || this.findFrom(e, -t) || new q(e.node(0));
  }
  static atStart(e) {
    return ze(e, e, 0, 0, 1) || new q(e);
  }
  static atEnd(e) {
    return ze(e, e, e.content.size, e.childCount, -1) || new q(e);
  }
  static fromJSON(e, t) {
    if (!t || !t.type)
      throw new RangeError("Invalid input for Selection.fromJSON");
    let n = $t[t.type];
    if (!n)
      throw new RangeError(`No selection type ${t.type} defined`);
    return n.fromJSON(e, t);
  }
  static jsonID(e, t) {
    if (e in $t)
      throw new RangeError("Duplicate use of selection JSON ID " + e);
    return $t[e] = t, t.prototype.jsonID = e, t;
  }
  getBookmark() {
    return O.between(this.$anchor, this.$head).getBookmark();
  }
}
C.prototype.visible = !0;
class $s {
  constructor(e, t) {
    this.$from = e, this.$to = t;
  }
}
let Hn = !1;
function $n(r) {
  !Hn && !r.parent.inlineContent && (Hn = !0, console.warn("TextSelection endpoint not pointing into a node with inline content (" + r.parent.type.name + ")"));
}
class O extends C {
  constructor(e, t = e) {
    $n(e), $n(t), super(e, t);
  }
  get $cursor() {
    return this.$anchor.pos == this.$head.pos ? this.$head : null;
  }
  map(e, t) {
    let n = e.resolve(t.map(this.head));
    if (!n.parent.inlineContent)
      return C.near(n);
    let i = e.resolve(t.map(this.anchor));
    return new O(i.parent.inlineContent ? i : n, n);
  }
  replace(e, t = y.empty) {
    if (super.replace(e, t), t == y.empty) {
      let n = this.$from.marksAcross(this.$to);
      n && e.ensureMarks(n);
    }
  }
  eq(e) {
    return e instanceof O && e.anchor == this.anchor && e.head == this.head;
  }
  getBookmark() {
    return new At(this.anchor, this.head);
  }
  toJSON() {
    return { type: "text", anchor: this.anchor, head: this.head };
  }
  static fromJSON(e, t) {
    if (typeof t.anchor != "number" || typeof t.head != "number")
      throw new RangeError("Invalid input for TextSelection.fromJSON");
    return new O(e.resolve(t.anchor), e.resolve(t.head));
  }
  static create(e, t, n = t) {
    let i = e.resolve(t);
    return new this(i, n == t ? i : e.resolve(n));
  }
  static between(e, t, n) {
    let i = e.pos - t.pos;
    if ((!n || i) && (n = i >= 0 ? 1 : -1), !t.parent.inlineContent) {
      let s = C.findFrom(t, n, !0) || C.findFrom(t, -n, !0);
      if (s)
        t = s.$head;
      else
        return C.near(t, n);
    }
    return e.parent.inlineContent || (i == 0 ? e = t : (e = (C.findFrom(e, -n, !0) || C.findFrom(e, n, !0)).$anchor, e.pos < t.pos != i < 0 && (e = t))), new O(e, t);
  }
}
C.jsonID("text", O);
class At {
  constructor(e, t) {
    this.anchor = e, this.head = t;
  }
  map(e) {
    return new At(e.map(this.anchor), e.map(this.head));
  }
  resolve(e) {
    return O.between(e.resolve(this.anchor), e.resolve(this.head));
  }
}
class x extends C {
  constructor(e) {
    let t = e.nodeAfter, n = e.node(0).resolve(e.pos + t.nodeSize);
    super(e, n), this.node = t;
  }
  map(e, t) {
    let { deleted: n, pos: i } = t.mapResult(this.anchor), s = e.resolve(i);
    return n ? C.near(s) : new x(s);
  }
  content() {
    return new y(g.from(this.node), 0, 0);
  }
  eq(e) {
    return e instanceof x && e.anchor == this.anchor;
  }
  toJSON() {
    return { type: "node", anchor: this.anchor };
  }
  getBookmark() {
    return new Cn(this.anchor);
  }
  static fromJSON(e, t) {
    if (typeof t.anchor != "number")
      throw new RangeError("Invalid input for NodeSelection.fromJSON");
    return new x(e.resolve(t.anchor));
  }
  static create(e, t) {
    return new x(e.resolve(t));
  }
  static isSelectable(e) {
    return !e.isText && e.type.spec.selectable !== !1;
  }
}
x.prototype.visible = !1;
C.jsonID("node", x);
class Cn {
  constructor(e) {
    this.anchor = e;
  }
  map(e) {
    let { deleted: t, pos: n } = e.mapResult(this.anchor);
    return t ? new At(n, n) : new Cn(n);
  }
  resolve(e) {
    let t = e.resolve(this.anchor), n = t.nodeAfter;
    return n && x.isSelectable(n) ? new x(t) : C.near(t);
  }
}
class q extends C {
  constructor(e) {
    super(e.resolve(0), e.resolve(e.content.size));
  }
  replace(e, t = y.empty) {
    if (t == y.empty) {
      e.delete(0, e.doc.content.size);
      let n = C.atStart(e.doc);
      n.eq(e.selection) || e.setSelection(n);
    } else
      super.replace(e, t);
  }
  toJSON() {
    return { type: "all" };
  }
  static fromJSON(e) {
    return new q(e);
  }
  map(e) {
    return new q(e);
  }
  eq(e) {
    return e instanceof q;
  }
  getBookmark() {
    return Us;
  }
}
C.jsonID("all", q);
const Us = {
  map() {
    return this;
  },
  resolve(r) {
    return new q(r);
  }
};
function ze(r, e, t, n, i, s = !1) {
  if (e.inlineContent)
    return O.create(r, t);
  for (let o = n - (i > 0 ? 0 : 1); i > 0 ? o < e.childCount : o >= 0; o += i) {
    let l = e.child(o);
    if (l.isAtom) {
      if (!s && x.isSelectable(l))
        return x.create(r, t - (i < 0 ? l.nodeSize : 0));
    } else {
      let a = ze(r, l, t + i, i < 0 ? l.childCount : 0, i, s);
      if (a)
        return a;
    }
    t += l.nodeSize * i;
  }
  return null;
}
function Un(r, e, t) {
  let n = r.steps.length - 1;
  if (n < e)
    return;
  let i = r.steps[n];
  if (!(i instanceof I || i instanceof W))
    return;
  let s = r.mapping.maps[n], o;
  s.forEach((l, a, h, c) => {
    o == null && (o = c);
  }), r.setSelection(C.near(r.doc.resolve(o), t));
}
const jn = 1, ft = 2, Gn = 4;
class js extends Hs {
  constructor(e) {
    super(e.doc), this.curSelectionFor = 0, this.updated = 0, this.meta = /* @__PURE__ */ Object.create(null), this.time = Date.now(), this.curSelection = e.selection, this.storedMarks = e.storedMarks;
  }
  get selection() {
    return this.curSelectionFor < this.steps.length && (this.curSelection = this.curSelection.map(this.doc, this.mapping.slice(this.curSelectionFor)), this.curSelectionFor = this.steps.length), this.curSelection;
  }
  setSelection(e) {
    if (e.$from.doc != this.doc)
      throw new RangeError("Selection passed to setSelection must point at the current document");
    return this.curSelection = e, this.curSelectionFor = this.steps.length, this.updated = (this.updated | jn) & ~ft, this.storedMarks = null, this;
  }
  get selectionSet() {
    return (this.updated & jn) > 0;
  }
  setStoredMarks(e) {
    return this.storedMarks = e, this.updated |= ft, this;
  }
  ensureMarks(e) {
    return M.sameSet(this.storedMarks || this.selection.$from.marks(), e) || this.setStoredMarks(e), this;
  }
  addStoredMark(e) {
    return this.ensureMarks(e.addToSet(this.storedMarks || this.selection.$head.marks()));
  }
  removeStoredMark(e) {
    return this.ensureMarks(e.removeFromSet(this.storedMarks || this.selection.$head.marks()));
  }
  get storedMarksSet() {
    return (this.updated & ft) > 0;
  }
  addStep(e, t) {
    super.addStep(e, t), this.updated = this.updated & ~ft, this.storedMarks = null;
  }
  setTime(e) {
    return this.time = e, this;
  }
  replaceSelection(e) {
    return this.selection.replace(this, e), this;
  }
  replaceSelectionWith(e, t = !0) {
    let n = this.selection;
    return t && (e = e.mark(this.storedMarks || (n.empty ? n.$from.marks() : n.$from.marksAcross(n.$to) || M.none))), n.replaceWith(this, e), this;
  }
  deleteSelection() {
    return this.selection.replace(this), this;
  }
  insertText(e, t, n) {
    let i = this.doc.type.schema;
    if (t == null)
      return e ? this.replaceSelectionWith(i.text(e), !0) : this.deleteSelection();
    {
      if (n == null && (n = t), n = n == null ? t : n, !e)
        return this.deleteRange(t, n);
      let s = this.storedMarks;
      if (!s) {
        let o = this.doc.resolve(t);
        s = n == t ? o.marks() : o.marksAcross(this.doc.resolve(n));
      }
      return this.replaceRangeWith(t, n, i.text(e, s)), this.selection.empty || this.setSelection(C.near(this.selection.$to)), this;
    }
  }
  setMeta(e, t) {
    return this.meta[typeof e == "string" ? e : e.key] = t, this;
  }
  getMeta(e) {
    return this.meta[typeof e == "string" ? e : e.key];
  }
  get isGeneric() {
    for (let e in this.meta)
      return !1;
    return !0;
  }
  scrollIntoView() {
    return this.updated |= Gn, this;
  }
  get scrolledIntoView() {
    return (this.updated & Gn) > 0;
  }
}
function Yn(r, e) {
  return !e || !r ? r : r.bind(e);
}
class Qe {
  constructor(e, t, n) {
    this.name = e, this.init = Yn(t.init, n), this.apply = Yn(t.apply, n);
  }
}
const Gs = [
  new Qe("doc", {
    init(r) {
      return r.doc || r.schema.topNodeType.createAndFill();
    },
    apply(r) {
      return r.doc;
    }
  }),
  new Qe("selection", {
    init(r, e) {
      return r.selection || C.atStart(e.doc);
    },
    apply(r) {
      return r.selection;
    }
  }),
  new Qe("storedMarks", {
    init(r) {
      return r.storedMarks || null;
    },
    apply(r, e, t, n) {
      return n.selection.$cursor ? r.storedMarks : null;
    }
  }),
  new Qe("scrollToSelection", {
    init() {
      return 0;
    },
    apply(r, e) {
      return r.scrolledIntoView ? e + 1 : e;
    }
  })
];
class Ut {
  constructor(e, t) {
    this.schema = e, this.plugins = [], this.pluginsByKey = /* @__PURE__ */ Object.create(null), this.fields = Gs.slice(), t && t.forEach((n) => {
      if (this.pluginsByKey[n.key])
        throw new RangeError("Adding different instances of a keyed plugin (" + n.key + ")");
      this.plugins.push(n), this.pluginsByKey[n.key] = n, n.spec.state && this.fields.push(new Qe(n.key, n.spec.state, n));
    });
  }
}
class ve {
  constructor(e) {
    this.config = e;
  }
  get schema() {
    return this.config.schema;
  }
  get plugins() {
    return this.config.plugins;
  }
  apply(e) {
    return this.applyTransaction(e).state;
  }
  filterTransaction(e, t = -1) {
    for (let n = 0; n < this.config.plugins.length; n++)
      if (n != t) {
        let i = this.config.plugins[n];
        if (i.spec.filterTransaction && !i.spec.filterTransaction.call(i, e, this))
          return !1;
      }
    return !0;
  }
  applyTransaction(e) {
    if (!this.filterTransaction(e))
      return { state: this, transactions: [] };
    let t = [e], n = this.applyInner(e), i = null;
    for (; ; ) {
      let s = !1;
      for (let o = 0; o < this.config.plugins.length; o++) {
        let l = this.config.plugins[o];
        if (l.spec.appendTransaction) {
          let a = i ? i[o].n : 0, h = i ? i[o].state : this, c = a < t.length && l.spec.appendTransaction.call(l, a ? t.slice(a) : t, h, n);
          if (c && n.filterTransaction(c, o)) {
            if (c.setMeta("appendedTransaction", e), !i) {
              i = [];
              for (let f = 0; f < this.config.plugins.length; f++)
                i.push(f < o ? { state: n, n: t.length } : { state: this, n: 0 });
            }
            t.push(c), n = n.applyInner(c), s = !0;
          }
          i && (i[o] = { state: n, n: t.length });
        }
      }
      if (!s)
        return { state: n, transactions: t };
    }
  }
  applyInner(e) {
    if (!e.before.eq(this.doc))
      throw new RangeError("Applying a mismatched transaction");
    let t = new ve(this.config), n = this.config.fields;
    for (let i = 0; i < n.length; i++) {
      let s = n[i];
      t[s.name] = s.apply(e, this[s.name], this, t);
    }
    return t;
  }
  get tr() {
    return new js(this);
  }
  static create(e) {
    let t = new Ut(e.doc ? e.doc.type.schema : e.schema, e.plugins), n = new ve(t);
    for (let i = 0; i < t.fields.length; i++)
      n[t.fields[i].name] = t.fields[i].init(e, n);
    return n;
  }
  reconfigure(e) {
    let t = new Ut(this.schema, e.plugins), n = t.fields, i = new ve(t);
    for (let s = 0; s < n.length; s++) {
      let o = n[s].name;
      i[o] = this.hasOwnProperty(o) ? this[o] : n[s].init(e, i);
    }
    return i;
  }
  toJSON(e) {
    let t = { doc: this.doc.toJSON(), selection: this.selection.toJSON() };
    if (this.storedMarks && (t.storedMarks = this.storedMarks.map((n) => n.toJSON())), e && typeof e == "object")
      for (let n in e) {
        if (n == "doc" || n == "selection")
          throw new RangeError("The JSON fields `doc` and `selection` are reserved");
        let i = e[n], s = i.spec.state;
        s && s.toJSON && (t[n] = s.toJSON.call(i, this[i.key]));
      }
    return t;
  }
  static fromJSON(e, t, n) {
    if (!t)
      throw new RangeError("Invalid input for EditorState.fromJSON");
    if (!e.schema)
      throw new RangeError("Required config field 'schema' missing");
    let i = new Ut(e.schema, e.plugins), s = new ve(i);
    return i.fields.forEach((o) => {
      if (o.name == "doc")
        s.doc = te.fromJSON(e.schema, t.doc);
      else if (o.name == "selection")
        s.selection = C.fromJSON(s.doc, t.selection);
      else if (o.name == "storedMarks")
        t.storedMarks && (s.storedMarks = t.storedMarks.map(e.schema.markFromJSON));
      else {
        if (n)
          for (let l in n) {
            let a = n[l], h = a.spec.state;
            if (a.key == o.name && h && h.fromJSON && Object.prototype.hasOwnProperty.call(t, l)) {
              s[o.name] = h.fromJSON.call(a, e, t[l], s);
              return;
            }
          }
        s[o.name] = o.init(e, s);
      }
    }), s;
  }
}
function ri(r, e, t) {
  for (let n in r) {
    let i = r[n];
    i instanceof Function ? i = i.bind(e) : n == "handleDOMEvents" && (i = ri(i, e, {})), t[n] = i;
  }
  return t;
}
class It {
  constructor(e) {
    this.spec = e, this.props = {}, e.props && ri(e.props, this, this.props), this.key = e.key ? e.key.key : ii("plugin");
  }
  getState(e) {
    return e[this.key];
  }
}
const jt = /* @__PURE__ */ Object.create(null);
function ii(r) {
  return r in jt ? r + "$" + ++jt[r] : (jt[r] = 0, r + "$");
}
class On {
  constructor(e = "key") {
    this.key = ii(e);
  }
  get(e) {
    return e.config.pluginsByKey[this.key];
  }
  getState(e) {
    return e[this.key];
  }
}
const si = (r, e) => r.selection.empty ? !1 : (e && e(r.tr.deleteSelection().scrollIntoView()), !0), Ys = (r, e, t) => {
  let { $cursor: n } = r.selection;
  if (!n || (t ? !t.endOfTextblock("backward", r) : n.parentOffset > 0))
    return !1;
  let i = oi(n);
  if (!i) {
    let o = n.blockRange(), l = o && bn(o);
    return l == null ? !1 : (e && e(r.tr.lift(o, l).scrollIntoView()), !0);
  }
  let s = i.nodeBefore;
  if (!s.type.spec.isolating && ai(r, i, e))
    return !0;
  if (n.parent.content.size == 0 && ($e(s, "end") || x.isSelectable(s))) {
    let o = Mn(r.doc, n.before(), n.after(), y.empty);
    if (o && o.slice.size < o.to - o.from) {
      if (e) {
        let l = r.tr.step(o);
        l.setSelection($e(s, "end") ? C.findFrom(l.doc.resolve(l.mapping.map(i.pos, -1)), -1) : x.create(l.doc, i.pos - s.nodeSize)), e(l.scrollIntoView());
      }
      return !0;
    }
  }
  return s.isAtom && i.depth == n.depth - 1 ? (e && e(r.tr.delete(i.pos - s.nodeSize, i.pos).scrollIntoView()), !0) : !1;
};
function $e(r, e, t = !1) {
  for (let n = r; n; n = e == "start" ? n.firstChild : n.lastChild) {
    if (n.isTextblock)
      return !0;
    if (t && n.childCount != 1)
      return !1;
  }
  return !1;
}
const Xs = (r, e, t) => {
  let { $head: n, empty: i } = r.selection, s = n;
  if (!i)
    return !1;
  if (n.parent.isTextblock) {
    if (t ? !t.endOfTextblock("backward", r) : n.parentOffset > 0)
      return !1;
    s = oi(n);
  }
  let o = s && s.nodeBefore;
  return !o || !x.isSelectable(o) ? !1 : (e && e(r.tr.setSelection(x.create(r.doc, s.pos - o.nodeSize)).scrollIntoView()), !0);
};
function oi(r) {
  if (!r.parent.type.spec.isolating)
    for (let e = r.depth - 1; e >= 0; e--) {
      if (r.index(e) > 0)
        return r.doc.resolve(r.before(e + 1));
      if (r.node(e).type.spec.isolating)
        break;
    }
  return null;
}
const Zs = (r, e, t) => {
  let { $cursor: n } = r.selection;
  if (!n || (t ? !t.endOfTextblock("forward", r) : n.parentOffset < n.parent.content.size))
    return !1;
  let i = li(n);
  if (!i)
    return !1;
  let s = i.nodeAfter;
  if (ai(r, i, e))
    return !0;
  if (n.parent.content.size == 0 && ($e(s, "start") || x.isSelectable(s))) {
    let o = Mn(r.doc, n.before(), n.after(), y.empty);
    if (o && o.slice.size < o.to - o.from) {
      if (e) {
        let l = r.tr.step(o);
        l.setSelection($e(s, "start") ? C.findFrom(l.doc.resolve(l.mapping.map(i.pos)), 1) : x.create(l.doc, l.mapping.map(i.pos))), e(l.scrollIntoView());
      }
      return !0;
    }
  }
  return s.isAtom && i.depth == n.depth - 1 ? (e && e(r.tr.delete(i.pos, i.pos + s.nodeSize).scrollIntoView()), !0) : !1;
}, Qs = (r, e, t) => {
  let { $head: n, empty: i } = r.selection, s = n;
  if (!i)
    return !1;
  if (n.parent.isTextblock) {
    if (t ? !t.endOfTextblock("forward", r) : n.parentOffset < n.parent.content.size)
      return !1;
    s = li(n);
  }
  let o = s && s.nodeAfter;
  return !o || !x.isSelectable(o) ? !1 : (e && e(r.tr.setSelection(x.create(r.doc, s.pos)).scrollIntoView()), !0);
};
function li(r) {
  if (!r.parent.type.spec.isolating)
    for (let e = r.depth - 1; e >= 0; e--) {
      let t = r.node(e);
      if (r.index(e) + 1 < t.childCount)
        return r.doc.resolve(r.after(e + 1));
      if (t.type.spec.isolating)
        break;
    }
  return null;
}
const _s = (r, e) => {
  let { $head: t, $anchor: n } = r.selection;
  return !t.parent.type.spec.code || !t.sameParent(n) ? !1 : (e && e(r.tr.insertText(`
`).scrollIntoView()), !0);
};
function Nn(r) {
  for (let e = 0; e < r.edgeCount; e++) {
    let { type: t } = r.edge(e);
    if (t.isTextblock && !t.hasRequiredAttrs())
      return t;
  }
  return null;
}
const eo = (r, e) => {
  let { $head: t, $anchor: n } = r.selection;
  if (!t.parent.type.spec.code || !t.sameParent(n))
    return !1;
  let i = t.node(-1), s = t.indexAfter(-1), o = Nn(i.contentMatchAt(s));
  if (!o || !i.canReplaceWith(s, s, o))
    return !1;
  if (e) {
    let l = t.after(), a = r.tr.replaceWith(l, l, o.createAndFill());
    a.setSelection(C.near(a.doc.resolve(l), 1)), e(a.scrollIntoView());
  }
  return !0;
}, to = (r, e) => {
  let t = r.selection, { $from: n, $to: i } = t;
  if (t instanceof q || n.parent.inlineContent || i.parent.inlineContent)
    return !1;
  let s = Nn(i.parent.contentMatchAt(i.indexAfter()));
  if (!s || !s.isTextblock)
    return !1;
  if (e) {
    let o = (!n.parentOffset && i.index() < i.parent.childCount ? n : i).pos, l = r.tr.insert(o, s.createAndFill());
    l.setSelection(O.create(l.doc, o + 1)), e(l.scrollIntoView());
  }
  return !0;
}, no = (r, e) => {
  let { $cursor: t } = r.selection;
  if (!t || t.parent.content.size)
    return !1;
  if (t.depth > 1 && t.after() != t.end(-1)) {
    let s = t.before();
    if (mt(r.doc, s))
      return e && e(r.tr.split(s).scrollIntoView()), !0;
  }
  let n = t.blockRange(), i = n && bn(n);
  return i == null ? !1 : (e && e(r.tr.lift(n, i).scrollIntoView()), !0);
}, ro = (r, e) => {
  let { $from: t, $to: n } = r.selection;
  if (r.selection instanceof x && r.selection.node.isBlock)
    return !t.parentOffset || !mt(r.doc, t.pos) ? !1 : (e && e(r.tr.split(t.pos).scrollIntoView()), !0);
  if (!t.parent.isBlock)
    return !1;
  if (e) {
    let i = n.parentOffset == n.parent.content.size, s = r.tr;
    (r.selection instanceof O || r.selection instanceof q) && s.deleteSelection();
    let o = t.depth == 0 ? null : Nn(t.node(-1).contentMatchAt(t.indexAfter(-1))), l = i && o ? [{ type: o }] : void 0, a = mt(s.doc, s.mapping.map(t.pos), 1, l);
    if (!l && !a && mt(s.doc, s.mapping.map(t.pos), 1, o ? [{ type: o }] : void 0) && (o && (l = [{ type: o }]), a = !0), a && (s.split(s.mapping.map(t.pos), 1, l), !i && !t.parentOffset && t.parent.type != o)) {
      let h = s.mapping.map(t.before()), c = s.doc.resolve(h);
      o && t.node(-1).canReplaceWith(c.index(), c.index() + 1, o) && s.setNodeMarkup(s.mapping.map(t.before()), o);
    }
    e(s.scrollIntoView());
  }
  return !0;
}, io = (r, e) => (e && e(r.tr.setSelection(new q(r.doc))), !0);
function so(r, e, t) {
  let n = e.nodeBefore, i = e.nodeAfter, s = e.index();
  return !n || !i || !n.type.compatibleContent(i.type) ? !1 : !n.content.size && e.parent.canReplace(s - 1, s) ? (t && t(r.tr.delete(e.pos - n.nodeSize, e.pos).scrollIntoView()), !0) : !e.parent.canReplace(s, s + 1) || !(i.isTextblock || Qr(r.doc, e.pos)) ? !1 : (t && t(r.tr.clearIncompatible(e.pos, n.type, n.contentMatchAt(n.childCount)).join(e.pos).scrollIntoView()), !0);
}
function ai(r, e, t) {
  let n = e.nodeBefore, i = e.nodeAfter, s, o;
  if (n.type.spec.isolating || i.type.spec.isolating)
    return !1;
  if (so(r, e, t))
    return !0;
  let l = e.parent.canReplace(e.index(), e.index() + 1);
  if (l && (s = (o = n.contentMatchAt(n.childCount)).findWrapping(i.type)) && o.matchType(s[0] || i.type).validEnd) {
    if (t) {
      let f = e.pos + i.nodeSize, d = g.empty;
      for (let m = s.length - 1; m >= 0; m--)
        d = g.from(s[m].create(null, d));
      d = g.from(n.copy(d));
      let u = r.tr.step(new W(e.pos - 1, f, e.pos, f, new y(d, 1, 0), s.length, !0)), p = f + 2 * s.length;
      Qr(u.doc, p) && u.join(p), t(u.scrollIntoView());
    }
    return !0;
  }
  let a = C.findFrom(e, 1), h = a && a.$from.blockRange(a.$to), c = h && bn(h);
  if (c != null && c >= e.depth)
    return t && t(r.tr.lift(h, c).scrollIntoView()), !0;
  if (l && $e(i, "start", !0) && $e(n, "end")) {
    let f = n, d = [];
    for (; d.push(f), !f.isTextblock; )
      f = f.lastChild;
    let u = i, p = 1;
    for (; !u.isTextblock; u = u.firstChild)
      p++;
    if (f.canReplace(f.childCount, f.childCount, u.content)) {
      if (t) {
        let m = g.empty;
        for (let S = d.length - 1; S >= 0; S--)
          m = g.from(d[S].copy(m));
        let k = r.tr.step(new W(e.pos - d.length, e.pos + i.nodeSize, e.pos + p, e.pos + i.nodeSize - p, new y(m, d.length, 0), 0, !0));
        t(k.scrollIntoView());
      }
      return !0;
    }
  }
  return !1;
}
function hi(r) {
  return function(e, t) {
    let n = e.selection, i = r < 0 ? n.$from : n.$to, s = i.depth;
    for (; i.node(s).isInline; ) {
      if (!s)
        return !1;
      s--;
    }
    return i.node(s).isTextblock ? (t && t(e.tr.setSelection(O.create(e.doc, r < 0 ? i.start(s) : i.end(s)))), !0) : !1;
  };
}
const oo = hi(-1), lo = hi(1);
function ao(r, e, t) {
  for (let n = 0; n < e.length; n++) {
    let { $from: i, $to: s } = e[n], o = i.depth == 0 ? r.inlineContent && r.type.allowsMarkType(t) : !1;
    if (r.nodesBetween(i.pos, s.pos, (l) => {
      if (o)
        return !1;
      o = l.inlineContent && l.type.allowsMarkType(t);
    }), o)
      return !0;
  }
  return !1;
}
function dt(r, e = null) {
  return function(t, n) {
    let { empty: i, $cursor: s, ranges: o } = t.selection;
    if (i && !s || !ao(t.doc, o, r))
      return !1;
    if (n)
      if (s)
        r.isInSet(t.storedMarks || s.marks()) ? n(t.tr.removeStoredMark(r)) : n(t.tr.addStoredMark(r.create(e)));
      else {
        let l = !1, a = t.tr;
        for (let h = 0; !l && h < o.length; h++) {
          let { $from: c, $to: f } = o[h];
          l = t.doc.rangeHasMark(c.pos, f.pos, r);
        }
        for (let h = 0; h < o.length; h++) {
          let { $from: c, $to: f } = o[h];
          if (l)
            a.removeMark(c.pos, f.pos, r);
          else {
            let d = c.pos, u = f.pos, p = c.nodeAfter, m = f.nodeBefore, k = p && p.isText ? /^\s*/.exec(p.text)[0].length : 0, S = m && m.isText ? /\s*$/.exec(m.text)[0].length : 0;
            d + k < u && (d += k, u -= S), a.addMark(d, u, r.create(e));
          }
        }
        n(a.scrollIntoView());
      }
    return !0;
  };
}
function wn(...r) {
  return function(e, t, n) {
    for (let i = 0; i < r.length; i++)
      if (r[i](e, t, n))
        return !0;
    return !1;
  };
}
let Gt = wn(si, Ys, Xs), Xn = wn(si, Zs, Qs);
const se = {
  Enter: wn(_s, to, no, ro),
  "Mod-Enter": eo,
  Backspace: Gt,
  "Mod-Backspace": Gt,
  "Shift-Backspace": Gt,
  Delete: Xn,
  "Mod-Delete": Xn,
  "Mod-a": io
}, ci = {
  "Ctrl-h": se.Backspace,
  "Alt-Backspace": se["Mod-Backspace"],
  "Ctrl-d": se.Delete,
  "Ctrl-Alt-Backspace": se["Mod-Delete"],
  "Alt-Delete": se["Mod-Delete"],
  "Alt-d": se["Mod-Delete"],
  "Ctrl-a": oo,
  "Ctrl-e": lo
};
for (let r in se)
  ci[r] = se[r];
const ho = typeof navigator < "u" ? /Mac|iP(hone|[oa]d)/.test(navigator.platform) : typeof os < "u" && os.platform ? os.platform() == "darwin" : !1, co = ho ? ci : se;
var Ot = 200, E = function() {
};
E.prototype.append = function(e) {
  return e.length ? (e = E.from(e), !this.length && e || e.length < Ot && this.leafAppend(e) || this.length < Ot && e.leafPrepend(this) || this.appendInner(e)) : this;
};
E.prototype.prepend = function(e) {
  return e.length ? E.from(e).append(this) : this;
};
E.prototype.appendInner = function(e) {
  return new fo(this, e);
};
E.prototype.slice = function(e, t) {
  return e === void 0 && (e = 0), t === void 0 && (t = this.length), e >= t ? E.empty : this.sliceInner(Math.max(0, e), Math.min(this.length, t));
};
E.prototype.get = function(e) {
  if (!(e < 0 || e >= this.length))
    return this.getInner(e);
};
E.prototype.forEach = function(e, t, n) {
  t === void 0 && (t = 0), n === void 0 && (n = this.length), t <= n ? this.forEachInner(e, t, n, 0) : this.forEachInvertedInner(e, t, n, 0);
};
E.prototype.map = function(e, t, n) {
  t === void 0 && (t = 0), n === void 0 && (n = this.length);
  var i = [];
  return this.forEach(function(s, o) {
    return i.push(e(s, o));
  }, t, n), i;
};
E.from = function(e) {
  return e instanceof E ? e : e && e.length ? new fi(e) : E.empty;
};
var fi = /* @__PURE__ */ function(r) {
  function e(n) {
    r.call(this), this.values = n;
  }
  r && (e.__proto__ = r), e.prototype = Object.create(r && r.prototype), e.prototype.constructor = e;
  var t = { length: { configurable: !0 }, depth: { configurable: !0 } };
  return e.prototype.flatten = function() {
    return this.values;
  }, e.prototype.sliceInner = function(i, s) {
    return i == 0 && s == this.length ? this : new e(this.values.slice(i, s));
  }, e.prototype.getInner = function(i) {
    return this.values[i];
  }, e.prototype.forEachInner = function(i, s, o, l) {
    for (var a = s; a < o; a++)
      if (i(this.values[a], l + a) === !1)
        return !1;
  }, e.prototype.forEachInvertedInner = function(i, s, o, l) {
    for (var a = s - 1; a >= o; a--)
      if (i(this.values[a], l + a) === !1)
        return !1;
  }, e.prototype.leafAppend = function(i) {
    if (this.length + i.length <= Ot)
      return new e(this.values.concat(i.flatten()));
  }, e.prototype.leafPrepend = function(i) {
    if (this.length + i.length <= Ot)
      return new e(i.flatten().concat(this.values));
  }, t.length.get = function() {
    return this.values.length;
  }, t.depth.get = function() {
    return 0;
  }, Object.defineProperties(e.prototype, t), e;
}(E);
E.empty = new fi([]);
var fo = /* @__PURE__ */ function(r) {
  function e(t, n) {
    r.call(this), this.left = t, this.right = n, this.length = t.length + n.length, this.depth = Math.max(t.depth, n.depth) + 1;
  }
  return r && (e.__proto__ = r), e.prototype = Object.create(r && r.prototype), e.prototype.constructor = e, e.prototype.flatten = function() {
    return this.left.flatten().concat(this.right.flatten());
  }, e.prototype.getInner = function(n) {
    return n < this.left.length ? this.left.get(n) : this.right.get(n - this.left.length);
  }, e.prototype.forEachInner = function(n, i, s, o) {
    var l = this.left.length;
    if (i < l && this.left.forEachInner(n, i, Math.min(s, l), o) === !1 || s > l && this.right.forEachInner(n, Math.max(i - l, 0), Math.min(this.length, s) - l, o + l) === !1)
      return !1;
  }, e.prototype.forEachInvertedInner = function(n, i, s, o) {
    var l = this.left.length;
    if (i > l && this.right.forEachInvertedInner(n, i - l, Math.max(s, l) - l, o + l) === !1 || s < l && this.left.forEachInvertedInner(n, Math.min(i, l), s, o) === !1)
      return !1;
  }, e.prototype.sliceInner = function(n, i) {
    if (n == 0 && i == this.length)
      return this;
    var s = this.left.length;
    return i <= s ? this.left.slice(n, i) : n >= s ? this.right.slice(n - s, i - s) : this.left.slice(n, s).append(this.right.slice(0, i - s));
  }, e.prototype.leafAppend = function(n) {
    var i = this.right.leafAppend(n);
    if (i)
      return new e(this.left, i);
  }, e.prototype.leafPrepend = function(n) {
    var i = this.left.leafPrepend(n);
    if (i)
      return new e(i, this.right);
  }, e.prototype.appendInner = function(n) {
    return this.left.depth >= Math.max(this.right.depth, n.depth) + 1 ? new e(this.left, new e(this.right, n)) : new e(this, n);
  }, e;
}(E), di = E;
const uo = 500;
class U {
  constructor(e, t) {
    this.items = e, this.eventCount = t;
  }
  popEvent(e, t) {
    if (this.eventCount == 0)
      return null;
    let n = this.items.length;
    for (; ; n--)
      if (this.items.get(n - 1).selection) {
        --n;
        break;
      }
    let i, s;
    t && (i = this.remapping(n, this.items.length), s = i.maps.length);
    let o = e.tr, l, a, h = [], c = [];
    return this.items.forEach((f, d) => {
      if (!f.step) {
        i || (i = this.remapping(n, d + 1), s = i.maps.length), s--, c.push(f);
        return;
      }
      if (i) {
        c.push(new Z(f.map));
        let u = f.step.map(i.slice(s)), p;
        u && o.maybeStep(u).doc && (p = o.mapping.maps[o.mapping.maps.length - 1], h.push(new Z(p, void 0, void 0, h.length + c.length))), s--, p && i.appendMap(p, s);
      } else
        o.maybeStep(f.step);
      if (f.selection)
        return l = i ? f.selection.map(i.slice(s)) : f.selection, a = new U(this.items.slice(0, n).append(c.reverse().concat(h)), this.eventCount - 1), !1;
    }, this.items.length, 0), { remaining: a, transform: o, selection: l };
  }
  addTransform(e, t, n, i) {
    let s = [], o = this.eventCount, l = this.items, a = !i && l.length ? l.get(l.length - 1) : null;
    for (let c = 0; c < e.steps.length; c++) {
      let f = e.steps[c].invert(e.docs[c]), d = new Z(e.mapping.maps[c], f, t), u;
      (u = a && a.merge(d)) && (d = u, c ? s.pop() : l = l.slice(0, l.length - 1)), s.push(d), t && (o++, t = void 0), i || (a = d);
    }
    let h = o - n.depth;
    return h > mo && (l = po(l, h), o -= h), new U(l.append(s), o);
  }
  remapping(e, t) {
    let n = new Je();
    return this.items.forEach((i, s) => {
      let o = i.mirrorOffset != null && s - i.mirrorOffset >= e ? n.maps.length - i.mirrorOffset : void 0;
      n.appendMap(i.map, o);
    }, e, t), n;
  }
  addMaps(e) {
    return this.eventCount == 0 ? this : new U(this.items.append(e.map((t) => new Z(t))), this.eventCount);
  }
  rebased(e, t) {
    if (!this.eventCount)
      return this;
    let n = [], i = Math.max(0, this.items.length - t), s = e.mapping, o = e.steps.length, l = this.eventCount;
    this.items.forEach((d) => {
      d.selection && l--;
    }, i);
    let a = t;
    this.items.forEach((d) => {
      let u = s.getMirror(--a);
      if (u == null)
        return;
      o = Math.min(o, u);
      let p = s.maps[u];
      if (d.step) {
        let m = e.steps[u].invert(e.docs[u]), k = d.selection && d.selection.map(s.slice(a + 1, u));
        k && l++, n.push(new Z(p, m, k));
      } else
        n.push(new Z(p));
    }, i);
    let h = [];
    for (let d = t; d < o; d++)
      h.push(new Z(s.maps[d]));
    let c = this.items.slice(0, i).append(h).append(n), f = new U(c, l);
    return f.emptyItemCount() > uo && (f = f.compress(this.items.length - n.length)), f;
  }
  emptyItemCount() {
    let e = 0;
    return this.items.forEach((t) => {
      t.step || e++;
    }), e;
  }
  compress(e = this.items.length) {
    let t = this.remapping(0, e), n = t.maps.length, i = [], s = 0;
    return this.items.forEach((o, l) => {
      if (l >= e)
        i.push(o), o.selection && s++;
      else if (o.step) {
        let a = o.step.map(t.slice(n)), h = a && a.getMap();
        if (n--, h && t.appendMap(h, n), a) {
          let c = o.selection && o.selection.map(t.slice(n));
          c && s++;
          let f = new Z(h.invert(), a, c), d, u = i.length - 1;
          (d = i.length && i[u].merge(f)) ? i[u] = d : i.push(f);
        }
      } else
        o.map && n--;
    }, this.items.length, 0), new U(di.from(i.reverse()), s);
  }
}
U.empty = new U(di.empty, 0);
function po(r, e) {
  let t;
  return r.forEach((n, i) => {
    if (n.selection && e-- == 0)
      return t = i, !1;
  }), r.slice(t);
}
class Z {
  constructor(e, t, n, i) {
    this.map = e, this.step = t, this.selection = n, this.mirrorOffset = i;
  }
  merge(e) {
    if (this.step && e.step && !e.selection) {
      let t = e.step.merge(this.step);
      if (t)
        return new Z(t.getMap().invert(), t, this.selection);
    }
  }
}
class ce {
  constructor(e, t, n, i) {
    this.done = e, this.undone = t, this.prevRanges = n, this.prevTime = i;
  }
}
const mo = 20;
function go(r, e, t, n) {
  let i = t.getMeta(me), s;
  if (i)
    return i.historyState;
  t.getMeta(ko) && (r = new ce(r.done, r.undone, null, 0));
  let o = t.getMeta("appendedTransaction");
  if (t.steps.length == 0)
    return r;
  if (o && o.getMeta(me))
    return o.getMeta(me).redo ? new ce(r.done.addTransform(t, void 0, n, gt(e)), r.undone, Zn(t.mapping.maps[t.steps.length - 1]), r.prevTime) : new ce(r.done, r.undone.addTransform(t, void 0, n, gt(e)), null, r.prevTime);
  if (t.getMeta("addToHistory") !== !1 && !(o && o.getMeta("addToHistory") === !1)) {
    let l = r.prevTime == 0 || !o && (r.prevTime < (t.time || 0) - n.newGroupDelay || !yo(t, r.prevRanges)), a = o ? Yt(r.prevRanges, t.mapping) : Zn(t.mapping.maps[t.steps.length - 1]);
    return new ce(r.done.addTransform(t, l ? e.selection.getBookmark() : void 0, n, gt(e)), U.empty, a, t.time);
  } else
    return (s = t.getMeta("rebased")) ? new ce(r.done.rebased(t, s), r.undone.rebased(t, s), Yt(r.prevRanges, t.mapping), r.prevTime) : new ce(r.done.addMaps(t.mapping.maps), r.undone.addMaps(t.mapping.maps), Yt(r.prevRanges, t.mapping), r.prevTime);
}
function yo(r, e) {
  if (!e)
    return !1;
  if (!r.docChanged)
    return !0;
  let t = !1;
  return r.mapping.maps[0].forEach((n, i) => {
    for (let s = 0; s < e.length; s += 2)
      n <= e[s + 1] && i >= e[s] && (t = !0);
  }), t;
}
function Zn(r) {
  let e = [];
  return r.forEach((t, n, i, s) => e.push(i, s)), e;
}
function Yt(r, e) {
  if (!r)
    return null;
  let t = [];
  for (let n = 0; n < r.length; n += 2) {
    let i = e.map(r[n], 1), s = e.map(r[n + 1], -1);
    i <= s && t.push(i, s);
  }
  return t;
}
function ui(r, e, t, n) {
  let i = gt(e), s = me.get(e).spec.config, o = (n ? r.undone : r.done).popEvent(e, i);
  if (!o)
    return;
  let l = o.selection.resolve(o.transform.doc), a = (n ? r.done : r.undone).addTransform(o.transform, e.selection.getBookmark(), s, i), h = new ce(n ? a : o.remaining, n ? o.remaining : a, null, 0);
  t(o.transform.setSelection(l).setMeta(me, { redo: n, historyState: h }).scrollIntoView());
}
let Xt = !1, Qn = null;
function gt(r) {
  let e = r.plugins;
  if (Qn != e) {
    Xt = !1, Qn = e;
    for (let t = 0; t < e.length; t++)
      if (e[t].spec.historyPreserveItems) {
        Xt = !0;
        break;
      }
  }
  return Xt;
}
const me = new On("history"), ko = new On("closeHistory");
function xo(r = {}) {
  return r = {
    depth: r.depth || 100,
    newGroupDelay: r.newGroupDelay || 500
  }, new It({
    key: me,
    state: {
      init() {
        return new ce(U.empty, U.empty, null, 0);
      },
      apply(e, t, n) {
        return go(t, n, e, r);
      }
    },
    config: r,
    props: {
      handleDOMEvents: {
        beforeinput(e, t) {
          let n = t.inputType, i = n == "historyUndo" ? pi : n == "historyRedo" ? mi : null;
          return i ? (t.preventDefault(), i(e.state, e.dispatch)) : !1;
        }
      }
    }
  });
}
const pi = (r, e) => {
  let t = me.getState(r);
  return !t || t.done.eventCount == 0 ? !1 : (e && ui(t, r, e, !1), !0);
}, mi = (r, e) => {
  let t = me.getState(r);
  return !t || t.undone.eventCount == 0 ? !1 : (e && ui(t, r, e, !0), !0);
};
var ke = {
  8: "Backspace",
  9: "Tab",
  10: "Enter",
  12: "NumLock",
  13: "Enter",
  16: "Shift",
  17: "Control",
  18: "Alt",
  20: "CapsLock",
  27: "Escape",
  32: " ",
  33: "PageUp",
  34: "PageDown",
  35: "End",
  36: "Home",
  37: "ArrowLeft",
  38: "ArrowUp",
  39: "ArrowRight",
  40: "ArrowDown",
  44: "PrintScreen",
  45: "Insert",
  46: "Delete",
  59: ";",
  61: "=",
  91: "Meta",
  92: "Meta",
  106: "*",
  107: "+",
  108: ",",
  109: "-",
  110: ".",
  111: "/",
  144: "NumLock",
  145: "ScrollLock",
  160: "Shift",
  161: "Shift",
  162: "Control",
  163: "Control",
  164: "Alt",
  165: "Alt",
  173: "-",
  186: ";",
  187: "=",
  188: ",",
  189: "-",
  190: ".",
  191: "/",
  192: "`",
  219: "[",
  220: "\\",
  221: "]",
  222: "'"
}, Nt = {
  48: ")",
  49: "!",
  50: "@",
  51: "#",
  52: "$",
  53: "%",
  54: "^",
  55: "&",
  56: "*",
  57: "(",
  59: ":",
  61: "+",
  173: "_",
  186: ":",
  187: "+",
  188: "<",
  189: "_",
  190: ">",
  191: "?",
  192: "~",
  219: "{",
  220: "|",
  221: "}",
  222: '"'
}, _n = typeof navigator < "u" && /Chrome\/(\d+)/.exec(navigator.userAgent);
typeof navigator < "u" && /Gecko\/\d+/.test(navigator.userAgent);
var So = typeof navigator < "u" && /Mac/.test(navigator.platform), bo = typeof navigator < "u" && /MSIE \d|Trident\/(?:[7-9]|\d{2,})\..*rv:(\d+)/.exec(navigator.userAgent), Mo = So || _n && +_n[1] < 57;
for (var D = 0; D < 10; D++)
  ke[48 + D] = ke[96 + D] = String(D);
for (var D = 1; D <= 24; D++)
  ke[D + 111] = "F" + D;
for (var D = 65; D <= 90; D++)
  ke[D] = String.fromCharCode(D + 32), Nt[D] = String.fromCharCode(D);
for (var Zt in ke)
  Nt.hasOwnProperty(Zt) || (Nt[Zt] = ke[Zt]);
function Co(r) {
  var e = Mo && (r.ctrlKey || r.altKey || r.metaKey) || bo && r.shiftKey && r.key && r.key.length == 1 || r.key == "Unidentified", t = !e && r.key || (r.shiftKey ? Nt : ke)[r.keyCode] || r.key || "Unidentified";
  return t == "Esc" && (t = "Escape"), t == "Del" && (t = "Delete"), t == "Left" && (t = "ArrowLeft"), t == "Up" && (t = "ArrowUp"), t == "Right" && (t = "ArrowRight"), t == "Down" && (t = "ArrowDown"), t;
}
const Oo = typeof navigator < "u" ? /Mac|iP(hone|[oa]d)/.test(navigator.platform) : !1;
function No(r) {
  let e = r.split(/-(?!$)/), t = e[e.length - 1];
  t == "Space" && (t = " ");
  let n, i, s, o;
  for (let l = 0; l < e.length - 1; l++) {
    let a = e[l];
    if (/^(cmd|meta|m)$/i.test(a))
      o = !0;
    else if (/^a(lt)?$/i.test(a))
      n = !0;
    else if (/^(c|ctrl|control)$/i.test(a))
      i = !0;
    else if (/^s(hift)?$/i.test(a))
      s = !0;
    else if (/^mod$/i.test(a))
      Oo ? o = !0 : i = !0;
    else
      throw new Error("Unrecognized modifier name: " + a);
  }
  return n && (t = "Alt-" + t), i && (t = "Ctrl-" + t), o && (t = "Meta-" + t), s && (t = "Shift-" + t), t;
}
function wo(r) {
  let e = /* @__PURE__ */ Object.create(null);
  for (let t in r)
    e[No(t)] = r[t];
  return e;
}
function Qt(r, e, t) {
  return e.altKey && (r = "Alt-" + r), e.ctrlKey && (r = "Ctrl-" + r), e.metaKey && (r = "Meta-" + r), t !== !1 && e.shiftKey && (r = "Shift-" + r), r;
}
function er(r) {
  return new It({ props: { handleKeyDown: To(r) } });
}
function To(r) {
  let e = wo(r);
  return function(t, n) {
    let i = Co(n), s = i.length == 1 && i != " ", o, l = e[Qt(i, n, !s)];
    if (l && l(t.state, t.dispatch, t))
      return !0;
    if (s && (n.shiftKey || n.altKey || n.metaKey || i.charCodeAt(0) > 127) && (o = ke[n.keyCode]) && o != i) {
      let a = e[Qt(o, n, !0)];
      if (a && a(t.state, t.dispatch, t))
        return !0;
    } else if (s && n.shiftKey) {
      let a = e[Qt(i, n, !0)];
      if (a && a(t.state, t.dispatch, t))
        return !0;
    }
    return !1;
  };
}
class Re {
  constructor(e, t) {
    this.match = e, this.match = e, this.handler = typeof t == "string" ? Do(t) : t;
  }
}
function Do(r) {
  return function(e, t, n, i) {
    let s = r;
    if (t[1]) {
      let o = t[0].lastIndexOf(t[1]);
      s += t[0].slice(o + t[1].length), n += o;
      let l = n - i;
      l > 0 && (s = t[0].slice(o - l, o) + s, n = i);
    }
    return e.tr.insertText(s, n, i);
  };
}
const Eo = 500;
function Ao({ rules: r }) {
  let e = new It({
    state: {
      init() {
        return null;
      },
      apply(t, n) {
        let i = t.getMeta(this);
        return i || (t.selectionSet || t.docChanged ? null : n);
      }
    },
    props: {
      handleTextInput(t, n, i, s) {
        return tr(t, n, i, s, r, e);
      },
      handleDOMEvents: {
        compositionend: (t) => {
          setTimeout(() => {
            let { $cursor: n } = t.state.selection;
            n && tr(t, n.pos, n.pos, "", r, e);
          });
        }
      }
    },
    isInputRules: !0
  });
  return e;
}
function tr(r, e, t, n, i, s) {
  if (r.composing)
    return !1;
  let o = r.state, l = o.doc.resolve(e);
  if (l.parent.type.spec.code)
    return !1;
  let a = l.parent.textBetween(Math.max(0, l.parentOffset - Eo), l.parentOffset, null, "\uFFFC") + n;
  for (let h = 0; h < i.length; h++) {
    let c = i[h].match.exec(a), f = c && i[h].handler(o, c, e - (c[0].length - n.length), t);
    if (!!f)
      return r.dispatch(f.setMeta(s, { transform: f, from: e, to: t, text: n })), !0;
  }
  return !1;
}
new Re(/--$/, "\u2014");
new Re(/\.\.\.$/, "\u2026");
new Re(/(?:^|[\s\{\[\(\<'"\u2018\u201C])(")$/, "\u201C");
new Re(/"$/, "\u201D");
new Re(/(?:^|[\s\{\[\(\<'"\u2018\u201C])(')$/, "\u2018");
new Re(/'$/, "\u2019");
function ut(r, e) {
  return new Re(r, (t, n, i, s) => {
    const o = t.tr, l = i + 1, a = s;
    return o.addMark(l, a, e.create()), o.delete(i, l), o.removeStoredMark(e), o;
  });
}
const Io = /\*(.*?)\*$/, Ro = /_(.*?)_$/, Po = /~(.*?)~$/, zo = /```(.*?)```$/, Bo = [
  ut(Io, j.marks.strong),
  ut(Ro, j.marks.em),
  ut(Po, j.marks.s),
  ut(zo, j.marks.code)
], vo = Ao({ rules: Bo }), Fe = new On("tooltip"), Fo = (r) => new It({
  state: {
    init() {
      return !1;
    },
    apply(t, n) {
      const i = t.getMeta(Fe);
      return typeof i == "boolean" ? i : n;
    }
  },
  view(t) {
    return new Lo(t, r);
  },
  props: {
    handleDOMEvents: {
      blur(t) {
        t.dispatch(t.state.tr.setMeta(Fe, !1));
      },
      focus(t) {
        t.dispatch(t.state.tr.setMeta(Fe, !0));
      }
    }
  },
  key: Fe
}), Vo = [
  er(co),
  xo({ newGroupDelay: 300 }),
  er({
    "Mod-z": pi,
    "Mod-y": mi,
    "Mod-b": dt(j.marks.strong),
    "Mod-i": dt(j.marks.em),
    "Alt-s": dt(j.marks.s),
    "Mod-m": dt(j.marks.code)
  }),
  vo
];
class Lo {
  constructor(e, t) {
    re(this, "view");
    re(this, "tooltip");
    re(this, "bold");
    re(this, "italic");
    re(this, "strike");
    re(this, "code");
    re(this, "options");
    this.options = t, this.view = e, this.tooltip = document.createElement("div"), this.tooltip.className = "tooltip", this.bold = document.createElement("button"), this.italic = document.createElement("button"), this.strike = document.createElement("button"), this.code = document.createElement("button"), this.initButtons(), e.dom.parentNode && e.dom.parentNode.appendChild(this.tooltip), this.update(e, null);
  }
  initButtons() {
    const e = document.createElement("strong");
    e.textContent = "B", this.bold.setAttribute("type", "button"), this.bold.append(e), this.bold.addEventListener(
      "mousedown",
      (s) => this.setStyle(s, "strong")
    );
    const t = document.createElement("em");
    t.innerHTML = "<strong><em>I</em></strong>", this.italic.setAttribute("type", "button"), this.italic.append(t), this.italic.addEventListener(
      "mousedown",
      (s) => this.setStyle(s, "em")
    );
    const n = document.createElement("s");
    n.innerHTML = "<strong><s>S</s></strong>", this.strike.setAttribute("type", "button"), this.strike.append(n), this.strike.addEventListener(
      "mousedown",
      (s) => this.setStyle(s, "s")
    );
    const i = document.createElement("strong");
    i.innerHTML = "<strong>&lt;/&gt;</strong>", this.code.setAttribute("type", "button"), this.code.append(i), this.code.addEventListener(
      "mousedown",
      (s) => this.setStyle(s, "code")
    ), this.tooltip.appendChild(this.bold), this.tooltip.appendChild(this.italic), this.tooltip.appendChild(this.strike), this.tooltip.appendChild(this.code);
  }
  setStyle(e, t) {
    var h;
    e.preventDefault();
    const { from: n, to: i, $from: s } = this.view.state.selection;
    let o;
    (h = s.nodeAfter) != null && h.marks && (o = this.getMarksInSelection(s.nodeAfter.marks));
    const l = this.view.state.tr;
    if (o && o.has(t)) {
      const c = l.removeMark(
        n,
        i,
        j.marks[t].create()
      );
      this.view.dispatch(c);
      return;
    }
    const a = l.addMark(n, i, j.marks[t].create());
    this.view.dispatch(a);
  }
  getMarksInSelection(e) {
    const t = /* @__PURE__ */ new Set();
    return e.forEach((n) => {
      t.add(n.type.name);
    }), t;
  }
  toggleActiveButtonClass(e) {
    var t, n;
    if ((t = e.selection.$from.nodeAfter) != null && t.marks) {
      const i = this.getMarksInSelection(
        (n = e.selection.$from.nodeAfter) == null ? void 0 : n.marks
      );
      this.bold.classList.remove("active"), this.italic.classList.remove("active"), this.strike.classList.remove("active"), this.code.classList.remove("active"), i.forEach((s) => {
        switch (s) {
          case "strong":
            this.bold.classList.add("active");
            break;
          case "em":
            this.italic.classList.add("active");
            break;
          case "s":
            this.strike.classList.add("active");
            break;
          case "code":
            this.code.classList.add("active");
            break;
        }
      });
    }
  }
  update(e, t) {
    this.view = e;
    const n = e.state, i = Fe.getState(n);
    if (t && t.doc.eq(n.doc) && t.selection.eq(n.selection) && i === Fe.getState(t))
      return;
    if (n.selection.empty || !i) {
      this.tooltip.style.display = "none";
      return;
    }
    this.toggleActiveButtonClass(n), this.tooltip.style.display = "";
    const { from: s, to: o } = n.selection, l = e.coordsAtPos(s), a = e.coordsAtPos(o);
    this.setPosition(this.options.position, this.options.distance, l, a);
  }
  setPosition(e, t, n, i) {
    const s = this.tooltip.offsetParent.getBoundingClientRect();
    if (e === "TOP") {
      const o = s.bottom + t - n.top;
      this.tooltip.style.bottom = o + "px";
      const l = (n.left + i.right) / 2 - this.tooltip.offsetWidth / 2 - s.left;
      this.tooltip.style.left = l + "px";
    } else if (e === "BOTTOM") {
      this.tooltip.style.bottom = s.bottom - (n.bottom + this.tooltip.offsetHeight + t) + "px";
      const o = (n.left + i.right) / 2 - this.tooltip.offsetWidth / 2 - s.left;
      this.tooltip.style.left = o + "px";
    } else if (e === "LEFT") {
      const o = n.left - this.tooltip.offsetWidth - s.left - t, l = s.bottom - n.top - (n.bottom - n.top) / 2 - this.tooltip.offsetHeight / 2;
      this.tooltip.style.left = o + "px", this.tooltip.style.bottom = l + "px";
    } else {
      const o = i.right + t - s.left, l = s.bottom - n.top - (n.bottom - n.top) / 2 - this.tooltip.offsetHeight / 2;
      this.tooltip.style.left = o + "px", this.tooltip.style.bottom = l + "px";
    }
  }
  destroy() {
    this.tooltip.remove();
  }
}
const J = function(r) {
  for (var e = 0; ; e++)
    if (r = r.previousSibling, !r)
      return e;
}, st = function(r) {
  let e = r.assignedSlot || r.parentNode;
  return e && e.nodeType == 11 ? e.host : e;
};
let nr = null;
const ie = function(r, e, t) {
  let n = nr || (nr = document.createRange());
  return n.setEnd(r, t == null ? r.nodeValue.length : t), n.setStart(r, e || 0), n;
}, Ee = function(r, e, t, n) {
  return t && (rr(r, e, t, n, -1) || rr(r, e, t, n, 1));
}, Jo = /^(img|br|input|textarea|hr)$/i;
function rr(r, e, t, n, i) {
  for (; ; ) {
    if (r == t && e == n)
      return !0;
    if (e == (i < 0 ? 0 : Q(r))) {
      let s = r.parentNode;
      if (!s || s.nodeType != 1 || qo(r) || Jo.test(r.nodeName) || r.contentEditable == "false")
        return !1;
      e = J(r) + (i < 0 ? 0 : 1), r = s;
    } else if (r.nodeType == 1) {
      if (r = r.childNodes[e + (i < 0 ? -1 : 0)], r.contentEditable == "false")
        return !1;
      e = i < 0 ? Q(r) : 0;
    } else
      return !1;
  }
}
function Q(r) {
  return r.nodeType == 3 ? r.nodeValue.length : r.childNodes.length;
}
function Wo(r, e, t) {
  for (let n = e == 0, i = e == Q(r); n || i; ) {
    if (r == t)
      return !0;
    let s = J(r);
    if (r = r.parentNode, !r)
      return !1;
    n = n && s == 0, i = i && s == Q(r);
  }
}
function qo(r) {
  let e;
  for (let t = r; t && !(e = t.pmViewDesc); t = t.parentNode)
    ;
  return e && e.node && e.node.isBlock && (e.dom == r || e.contentDOM == r);
}
const Rt = function(r) {
  return r.focusNode && Ee(r.focusNode, r.focusOffset, r.anchorNode, r.anchorOffset);
};
function Ve(r, e) {
  let t = document.createEvent("Event");
  return t.initEvent("keydown", !0, !0), t.keyCode = r, t.key = t.code = e, t;
}
function Ko(r) {
  let e = r.activeElement;
  for (; e && e.shadowRoot; )
    e = e.shadowRoot.activeElement;
  return e;
}
const xe = typeof navigator < "u" ? navigator : null, ir = typeof document < "u" ? document : null, Se = xe && xe.userAgent || "", hn = /Edge\/(\d+)/.exec(Se), gi = /MSIE \d/.exec(Se), cn = /Trident\/(?:[7-9]|\d{2,})\..*rv:(\d+)/.exec(Se), F = !!(gi || cn || hn), ge = gi ? document.documentMode : cn ? +cn[1] : hn ? +hn[1] : 0, X = !F && /gecko\/(\d+)/i.test(Se);
X && +(/Firefox\/(\d+)/.exec(Se) || [0, 0])[1];
const fn = !F && /Chrome\/(\d+)/.exec(Se), v = !!fn, Ho = fn ? +fn[1] : 0, R = !F && !!xe && /Apple Computer/.test(xe.vendor), Ue = R && (/Mobile\/\w+/.test(Se) || !!xe && xe.maxTouchPoints > 2), H = Ue || (xe ? /Mac/.test(xe.platform) : !1), _ = /Android \d/.test(Se), Pt = !!ir && "webkitFontSmoothing" in ir.documentElement.style, $o = Pt ? +(/\bAppleWebKit\/(\d+)/.exec(navigator.userAgent) || [0, 0])[1] : 0;
function Uo(r) {
  return {
    left: 0,
    right: r.documentElement.clientWidth,
    top: 0,
    bottom: r.documentElement.clientHeight
  };
}
function ae(r, e) {
  return typeof r == "number" ? r : r[e];
}
function jo(r) {
  let e = r.getBoundingClientRect(), t = e.width / r.offsetWidth || 1, n = e.height / r.offsetHeight || 1;
  return {
    left: e.left,
    right: e.left + r.clientWidth * t,
    top: e.top,
    bottom: e.top + r.clientHeight * n
  };
}
function sr(r, e, t) {
  let n = r.someProp("scrollThreshold") || 0, i = r.someProp("scrollMargin") || 5, s = r.dom.ownerDocument;
  for (let o = t || r.dom; o; o = st(o)) {
    if (o.nodeType != 1)
      continue;
    let l = o, a = l == s.body, h = a ? Uo(s) : jo(l), c = 0, f = 0;
    if (e.top < h.top + ae(n, "top") ? f = -(h.top - e.top + ae(i, "top")) : e.bottom > h.bottom - ae(n, "bottom") && (f = e.bottom - h.bottom + ae(i, "bottom")), e.left < h.left + ae(n, "left") ? c = -(h.left - e.left + ae(i, "left")) : e.right > h.right - ae(n, "right") && (c = e.right - h.right + ae(i, "right")), c || f)
      if (a)
        s.defaultView.scrollBy(c, f);
      else {
        let d = l.scrollLeft, u = l.scrollTop;
        f && (l.scrollTop += f), c && (l.scrollLeft += c);
        let p = l.scrollLeft - d, m = l.scrollTop - u;
        e = { left: e.left - p, top: e.top - m, right: e.right - p, bottom: e.bottom - m };
      }
    if (a)
      break;
  }
}
function Go(r) {
  let e = r.dom.getBoundingClientRect(), t = Math.max(0, e.top), n, i;
  for (let s = (e.left + e.right) / 2, o = t + 1; o < Math.min(innerHeight, e.bottom); o += 5) {
    let l = r.root.elementFromPoint(s, o);
    if (!l || l == r.dom || !r.dom.contains(l))
      continue;
    let a = l.getBoundingClientRect();
    if (a.top >= t - 20) {
      n = l, i = a.top;
      break;
    }
  }
  return { refDOM: n, refTop: i, stack: yi(r.dom) };
}
function yi(r) {
  let e = [], t = r.ownerDocument;
  for (let n = r; n && (e.push({ dom: n, top: n.scrollTop, left: n.scrollLeft }), r != t); n = st(n))
    ;
  return e;
}
function Yo({ refDOM: r, refTop: e, stack: t }) {
  let n = r ? r.getBoundingClientRect().top : 0;
  ki(t, n == 0 ? 0 : n - e);
}
function ki(r, e) {
  for (let t = 0; t < r.length; t++) {
    let { dom: n, top: i, left: s } = r[t];
    n.scrollTop != i + e && (n.scrollTop = i + e), n.scrollLeft != s && (n.scrollLeft = s);
  }
}
let Pe = null;
function Xo(r) {
  if (r.setActive)
    return r.setActive();
  if (Pe)
    return r.focus(Pe);
  let e = yi(r);
  r.focus(Pe == null ? {
    get preventScroll() {
      return Pe = { preventScroll: !0 }, !0;
    }
  } : void 0), Pe || (Pe = !1, ki(e, 0));
}
function xi(r, e) {
  let t, n = 2e8, i, s = 0, o = e.top, l = e.top;
  for (let a = r.firstChild, h = 0; a; a = a.nextSibling, h++) {
    let c;
    if (a.nodeType == 1)
      c = a.getClientRects();
    else if (a.nodeType == 3)
      c = ie(a).getClientRects();
    else
      continue;
    for (let f = 0; f < c.length; f++) {
      let d = c[f];
      if (d.top <= o && d.bottom >= l) {
        o = Math.max(d.bottom, o), l = Math.min(d.top, l);
        let u = d.left > e.left ? d.left - e.left : d.right < e.left ? e.left - d.right : 0;
        if (u < n) {
          t = a, n = u, i = u && t.nodeType == 3 ? {
            left: d.right < e.left ? d.right : d.left,
            top: e.top
          } : e, a.nodeType == 1 && u && (s = h + (e.left >= (d.left + d.right) / 2 ? 1 : 0));
          continue;
        }
      }
      !t && (e.left >= d.right && e.top >= d.top || e.left >= d.left && e.top >= d.bottom) && (s = h + 1);
    }
  }
  return t && t.nodeType == 3 ? Zo(t, i) : !t || n && t.nodeType == 1 ? { node: r, offset: s } : xi(t, i);
}
function Zo(r, e) {
  let t = r.nodeValue.length, n = document.createRange();
  for (let i = 0; i < t; i++) {
    n.setEnd(r, i + 1), n.setStart(r, i);
    let s = he(n, 1);
    if (s.top != s.bottom && Tn(e, s))
      return { node: r, offset: i + (e.left >= (s.left + s.right) / 2 ? 1 : 0) };
  }
  return { node: r, offset: 0 };
}
function Tn(r, e) {
  return r.left >= e.left - 1 && r.left <= e.right + 1 && r.top >= e.top - 1 && r.top <= e.bottom + 1;
}
function Qo(r, e) {
  let t = r.parentNode;
  return t && /^li$/i.test(t.nodeName) && e.left < r.getBoundingClientRect().left ? t : r;
}
function _o(r, e, t) {
  let { node: n, offset: i } = xi(e, t), s = -1;
  if (n.nodeType == 1 && !n.firstChild) {
    let o = n.getBoundingClientRect();
    s = o.left != o.right && t.left > (o.left + o.right) / 2 ? 1 : -1;
  }
  return r.docView.posFromDOM(n, i, s);
}
function el(r, e, t, n) {
  let i = -1;
  for (let s = e; s != r.dom; ) {
    let o = r.docView.nearestDesc(s, !0);
    if (!o)
      return null;
    if (o.node.isBlock && o.parent) {
      let l = o.dom.getBoundingClientRect();
      if (l.left > n.left || l.top > n.top)
        i = o.posBefore;
      else if (l.right < n.left || l.bottom < n.top)
        i = o.posAfter;
      else
        break;
    }
    s = o.dom.parentNode;
  }
  return i > -1 ? i : r.docView.posFromDOM(e, t, 1);
}
function Si(r, e, t) {
  let n = r.childNodes.length;
  if (n && t.top < t.bottom)
    for (let i = Math.max(0, Math.min(n - 1, Math.floor(n * (e.top - t.top) / (t.bottom - t.top)) - 2)), s = i; ; ) {
      let o = r.childNodes[s];
      if (o.nodeType == 1) {
        let l = o.getClientRects();
        for (let a = 0; a < l.length; a++) {
          let h = l[a];
          if (Tn(e, h))
            return Si(o, e, h);
        }
      }
      if ((s = (s + 1) % n) == i)
        break;
    }
  return r;
}
function tl(r, e) {
  let t = r.dom.ownerDocument, n, i = 0;
  if (t.caretPositionFromPoint)
    try {
      let a = t.caretPositionFromPoint(e.left, e.top);
      a && ({ offsetNode: n, offset: i } = a);
    } catch {
    }
  if (!n && t.caretRangeFromPoint) {
    let a = t.caretRangeFromPoint(e.left, e.top);
    a && ({ startContainer: n, startOffset: i } = a);
  }
  let s = (r.root.elementFromPoint ? r.root : t).elementFromPoint(e.left, e.top), o;
  if (!s || !r.dom.contains(s.nodeType != 1 ? s.parentNode : s)) {
    let a = r.dom.getBoundingClientRect();
    if (!Tn(e, a) || (s = Si(r.dom, e, a), !s))
      return null;
  }
  if (R)
    for (let a = s; n && a; a = st(a))
      a.draggable && (n = void 0);
  if (s = Qo(s, e), n) {
    if (X && n.nodeType == 1 && (i = Math.min(i, n.childNodes.length), i < n.childNodes.length)) {
      let a = n.childNodes[i], h;
      a.nodeName == "IMG" && (h = a.getBoundingClientRect()).right <= e.left && h.bottom > e.top && i++;
    }
    n == r.dom && i == n.childNodes.length - 1 && n.lastChild.nodeType == 1 && e.top > n.lastChild.getBoundingClientRect().bottom ? o = r.state.doc.content.size : (i == 0 || n.nodeType != 1 || n.childNodes[i - 1].nodeName != "BR") && (o = el(r, n, i, e));
  }
  o == null && (o = _o(r, s, e));
  let l = r.docView.nearestDesc(s, !0);
  return { pos: o, inside: l ? l.posAtStart - l.border : -1 };
}
function he(r, e) {
  let t = r.getClientRects();
  return t.length ? t[e < 0 ? 0 : t.length - 1] : r.getBoundingClientRect();
}
const nl = /[\u0590-\u05f4\u0600-\u06ff\u0700-\u08ac]/;
function bi(r, e, t) {
  let { node: n, offset: i, atom: s } = r.docView.domFromPos(e, t < 0 ? -1 : 1), o = Pt || X;
  if (n.nodeType == 3)
    if (o && (nl.test(n.nodeValue) || (t < 0 ? !i : i == n.nodeValue.length))) {
      let a = he(ie(n, i, i), t);
      if (X && i && /\s/.test(n.nodeValue[i - 1]) && i < n.nodeValue.length) {
        let h = he(ie(n, i - 1, i - 1), -1);
        if (h.top == a.top) {
          let c = he(ie(n, i, i + 1), -1);
          if (c.top != a.top)
            return Ye(c, c.left < h.left);
        }
      }
      return a;
    } else {
      let a = i, h = i, c = t < 0 ? 1 : -1;
      return t < 0 && !i ? (h++, c = -1) : t >= 0 && i == n.nodeValue.length ? (a--, c = 1) : t < 0 ? a-- : h++, Ye(he(ie(n, a, h), 1), c < 0);
    }
  if (!r.state.doc.resolve(e - (s || 0)).parent.inlineContent) {
    if (s == null && i && (t < 0 || i == Q(n))) {
      let a = n.childNodes[i - 1];
      if (a.nodeType == 1)
        return _t(a.getBoundingClientRect(), !1);
    }
    if (s == null && i < Q(n)) {
      let a = n.childNodes[i];
      if (a.nodeType == 1)
        return _t(a.getBoundingClientRect(), !0);
    }
    return _t(n.getBoundingClientRect(), t >= 0);
  }
  if (s == null && i && (t < 0 || i == Q(n))) {
    let a = n.childNodes[i - 1], h = a.nodeType == 3 ? ie(a, Q(a) - (o ? 0 : 1)) : a.nodeType == 1 && (a.nodeName != "BR" || !a.nextSibling) ? a : null;
    if (h)
      return Ye(he(h, 1), !1);
  }
  if (s == null && i < Q(n)) {
    let a = n.childNodes[i];
    for (; a.pmViewDesc && a.pmViewDesc.ignoreForCoords; )
      a = a.nextSibling;
    let h = a ? a.nodeType == 3 ? ie(a, 0, o ? 0 : 1) : a.nodeType == 1 ? a : null : null;
    if (h)
      return Ye(he(h, -1), !0);
  }
  return Ye(he(n.nodeType == 3 ? ie(n) : n, -t), t >= 0);
}
function Ye(r, e) {
  if (r.width == 0)
    return r;
  let t = e ? r.left : r.right;
  return { top: r.top, bottom: r.bottom, left: t, right: t };
}
function _t(r, e) {
  if (r.height == 0)
    return r;
  let t = e ? r.top : r.bottom;
  return { top: t, bottom: t, left: r.left, right: r.right };
}
function Mi(r, e, t) {
  let n = r.state, i = r.root.activeElement;
  n != e && r.updateState(e), i != r.dom && r.focus();
  try {
    return t();
  } finally {
    n != e && r.updateState(n), i != r.dom && i && i.focus();
  }
}
function rl(r, e, t) {
  let n = e.selection, i = t == "up" ? n.$from : n.$to;
  return Mi(r, e, () => {
    let { node: s } = r.docView.domFromPos(i.pos, t == "up" ? -1 : 1);
    for (; ; ) {
      let l = r.docView.nearestDesc(s, !0);
      if (!l)
        break;
      if (l.node.isBlock) {
        s = l.dom;
        break;
      }
      s = l.dom.parentNode;
    }
    let o = bi(r, i.pos, 1);
    for (let l = s.firstChild; l; l = l.nextSibling) {
      let a;
      if (l.nodeType == 1)
        a = l.getClientRects();
      else if (l.nodeType == 3)
        a = ie(l, 0, l.nodeValue.length).getClientRects();
      else
        continue;
      for (let h = 0; h < a.length; h++) {
        let c = a[h];
        if (c.bottom > c.top + 1 && (t == "up" ? o.top - c.top > (c.bottom - o.top) * 2 : c.bottom - o.bottom > (o.bottom - c.top) * 2))
          return !1;
      }
    }
    return !0;
  });
}
const il = /[\u0590-\u08ac]/;
function sl(r, e, t) {
  let { $head: n } = e.selection;
  if (!n.parent.isTextblock)
    return !1;
  let i = n.parentOffset, s = !i, o = i == n.parent.content.size, l = r.domSelection();
  return !il.test(n.parent.textContent) || !l.modify ? t == "left" || t == "backward" ? s : o : Mi(r, e, () => {
    let { focusNode: a, focusOffset: h, anchorNode: c, anchorOffset: f } = r.domSelectionRange(), d = l.caretBidiLevel;
    l.modify("move", t, "character");
    let u = n.depth ? r.docView.domAfterPos(n.before()) : r.dom, { focusNode: p, focusOffset: m } = r.domSelectionRange(), k = p && !u.contains(p.nodeType == 1 ? p : p.parentNode) || a == p && h == m;
    try {
      l.collapse(c, f), a && (a != c || h != f) && l.extend && l.extend(a, h);
    } catch {
    }
    return d != null && (l.caretBidiLevel = d), k;
  });
}
let or = null, lr = null, ar = !1;
function ol(r, e, t) {
  return or == e && lr == t ? ar : (or = e, lr = t, ar = t == "up" || t == "down" ? rl(r, e, t) : sl(r, e, t));
}
const Y = 0, hr = 1, Le = 2, ne = 3;
class lt {
  constructor(e, t, n, i) {
    this.parent = e, this.children = t, this.dom = n, this.contentDOM = i, this.dirty = Y, n.pmViewDesc = this;
  }
  matchesWidget(e) {
    return !1;
  }
  matchesMark(e) {
    return !1;
  }
  matchesNode(e, t, n) {
    return !1;
  }
  matchesHack(e) {
    return !1;
  }
  parseRule() {
    return null;
  }
  stopEvent(e) {
    return !1;
  }
  get size() {
    let e = 0;
    for (let t = 0; t < this.children.length; t++)
      e += this.children[t].size;
    return e;
  }
  get border() {
    return 0;
  }
  destroy() {
    this.parent = void 0, this.dom.pmViewDesc == this && (this.dom.pmViewDesc = void 0);
    for (let e = 0; e < this.children.length; e++)
      this.children[e].destroy();
  }
  posBeforeChild(e) {
    for (let t = 0, n = this.posAtStart; ; t++) {
      let i = this.children[t];
      if (i == e)
        return n;
      n += i.size;
    }
  }
  get posBefore() {
    return this.parent.posBeforeChild(this);
  }
  get posAtStart() {
    return this.parent ? this.parent.posBeforeChild(this) + this.border : 0;
  }
  get posAfter() {
    return this.posBefore + this.size;
  }
  get posAtEnd() {
    return this.posAtStart + this.size - 2 * this.border;
  }
  localPosFromDOM(e, t, n) {
    if (this.contentDOM && this.contentDOM.contains(e.nodeType == 1 ? e : e.parentNode))
      if (n < 0) {
        let s, o;
        if (e == this.contentDOM)
          s = e.childNodes[t - 1];
        else {
          for (; e.parentNode != this.contentDOM; )
            e = e.parentNode;
          s = e.previousSibling;
        }
        for (; s && !((o = s.pmViewDesc) && o.parent == this); )
          s = s.previousSibling;
        return s ? this.posBeforeChild(o) + o.size : this.posAtStart;
      } else {
        let s, o;
        if (e == this.contentDOM)
          s = e.childNodes[t];
        else {
          for (; e.parentNode != this.contentDOM; )
            e = e.parentNode;
          s = e.nextSibling;
        }
        for (; s && !((o = s.pmViewDesc) && o.parent == this); )
          s = s.nextSibling;
        return s ? this.posBeforeChild(o) : this.posAtEnd;
      }
    let i;
    if (e == this.dom && this.contentDOM)
      i = t > J(this.contentDOM);
    else if (this.contentDOM && this.contentDOM != this.dom && this.dom.contains(this.contentDOM))
      i = e.compareDocumentPosition(this.contentDOM) & 2;
    else if (this.dom.firstChild) {
      if (t == 0)
        for (let s = e; ; s = s.parentNode) {
          if (s == this.dom) {
            i = !1;
            break;
          }
          if (s.previousSibling)
            break;
        }
      if (i == null && t == e.childNodes.length)
        for (let s = e; ; s = s.parentNode) {
          if (s == this.dom) {
            i = !0;
            break;
          }
          if (s.nextSibling)
            break;
        }
    }
    return (i == null ? n > 0 : i) ? this.posAtEnd : this.posAtStart;
  }
  nearestDesc(e, t = !1) {
    for (let n = !0, i = e; i; i = i.parentNode) {
      let s = this.getDesc(i), o;
      if (s && (!t || s.node))
        if (n && (o = s.nodeDOM) && !(o.nodeType == 1 ? o.contains(e.nodeType == 1 ? e : e.parentNode) : o == e))
          n = !1;
        else
          return s;
    }
  }
  getDesc(e) {
    let t = e.pmViewDesc;
    for (let n = t; n; n = n.parent)
      if (n == this)
        return t;
  }
  posFromDOM(e, t, n) {
    for (let i = e; i; i = i.parentNode) {
      let s = this.getDesc(i);
      if (s)
        return s.localPosFromDOM(e, t, n);
    }
    return -1;
  }
  descAt(e) {
    for (let t = 0, n = 0; t < this.children.length; t++) {
      let i = this.children[t], s = n + i.size;
      if (n == e && s != n) {
        for (; !i.border && i.children.length; )
          i = i.children[0];
        return i;
      }
      if (e < s)
        return i.descAt(e - n - i.border);
      n = s;
    }
  }
  domFromPos(e, t) {
    if (!this.contentDOM)
      return { node: this.dom, offset: 0, atom: e + 1 };
    let n = 0, i = 0;
    for (let s = 0; n < this.children.length; n++) {
      let o = this.children[n], l = s + o.size;
      if (l > e || o instanceof Oi) {
        i = e - s;
        break;
      }
      s = l;
    }
    if (i)
      return this.children[n].domFromPos(i - this.children[n].border, t);
    for (let s; n && !(s = this.children[n - 1]).size && s instanceof Ci && s.side >= 0; n--)
      ;
    if (t <= 0) {
      let s, o = !0;
      for (; s = n ? this.children[n - 1] : null, !(!s || s.dom.parentNode == this.contentDOM); n--, o = !1)
        ;
      return s && t && o && !s.border && !s.domAtom ? s.domFromPos(s.size, t) : { node: this.contentDOM, offset: s ? J(s.dom) + 1 : 0 };
    } else {
      let s, o = !0;
      for (; s = n < this.children.length ? this.children[n] : null, !(!s || s.dom.parentNode == this.contentDOM); n++, o = !1)
        ;
      return s && o && !s.border && !s.domAtom ? s.domFromPos(0, t) : { node: this.contentDOM, offset: s ? J(s.dom) : this.contentDOM.childNodes.length };
    }
  }
  parseRange(e, t, n = 0) {
    if (this.children.length == 0)
      return { node: this.contentDOM, from: e, to: t, fromOffset: 0, toOffset: this.contentDOM.childNodes.length };
    let i = -1, s = -1;
    for (let o = n, l = 0; ; l++) {
      let a = this.children[l], h = o + a.size;
      if (i == -1 && e <= h) {
        let c = o + a.border;
        if (e >= c && t <= h - a.border && a.node && a.contentDOM && this.contentDOM.contains(a.contentDOM))
          return a.parseRange(e, t, c);
        e = o;
        for (let f = l; f > 0; f--) {
          let d = this.children[f - 1];
          if (d.size && d.dom.parentNode == this.contentDOM && !d.emptyChildAt(1)) {
            i = J(d.dom) + 1;
            break;
          }
          e -= d.size;
        }
        i == -1 && (i = 0);
      }
      if (i > -1 && (h > t || l == this.children.length - 1)) {
        t = h;
        for (let c = l + 1; c < this.children.length; c++) {
          let f = this.children[c];
          if (f.size && f.dom.parentNode == this.contentDOM && !f.emptyChildAt(-1)) {
            s = J(f.dom);
            break;
          }
          t += f.size;
        }
        s == -1 && (s = this.contentDOM.childNodes.length);
        break;
      }
      o = h;
    }
    return { node: this.contentDOM, from: e, to: t, fromOffset: i, toOffset: s };
  }
  emptyChildAt(e) {
    if (this.border || !this.contentDOM || !this.children.length)
      return !1;
    let t = this.children[e < 0 ? 0 : this.children.length - 1];
    return t.size == 0 || t.emptyChildAt(e);
  }
  domAfterPos(e) {
    let { node: t, offset: n } = this.domFromPos(e, 0);
    if (t.nodeType != 1 || n == t.childNodes.length)
      throw new RangeError("No node after pos " + e);
    return t.childNodes[n];
  }
  setSelection(e, t, n, i = !1) {
    let s = Math.min(e, t), o = Math.max(e, t);
    for (let d = 0, u = 0; d < this.children.length; d++) {
      let p = this.children[d], m = u + p.size;
      if (s > u && o < m)
        return p.setSelection(e - u - p.border, t - u - p.border, n, i);
      u = m;
    }
    let l = this.domFromPos(e, e ? -1 : 1), a = t == e ? l : this.domFromPos(t, t ? -1 : 1), h = n.getSelection(), c = !1;
    if ((X || R) && e == t) {
      let { node: d, offset: u } = l;
      if (d.nodeType == 3) {
        if (c = !!(u && d.nodeValue[u - 1] == `
`), c && u == d.nodeValue.length)
          for (let p = d, m; p; p = p.parentNode) {
            if (m = p.nextSibling) {
              m.nodeName == "BR" && (l = a = { node: m.parentNode, offset: J(m) + 1 });
              break;
            }
            let k = p.pmViewDesc;
            if (k && k.node && k.node.isBlock)
              break;
          }
      } else {
        let p = d.childNodes[u - 1];
        c = p && (p.nodeName == "BR" || p.contentEditable == "false");
      }
    }
    if (X && h.focusNode && h.focusNode != a.node && h.focusNode.nodeType == 1) {
      let d = h.focusNode.childNodes[h.focusOffset];
      d && d.contentEditable == "false" && (i = !0);
    }
    if (!(i || c && R) && Ee(l.node, l.offset, h.anchorNode, h.anchorOffset) && Ee(a.node, a.offset, h.focusNode, h.focusOffset))
      return;
    let f = !1;
    if ((h.extend || e == t) && !c) {
      h.collapse(l.node, l.offset);
      try {
        e != t && h.extend(a.node, a.offset), f = !0;
      } catch {
      }
    }
    if (!f) {
      if (e > t) {
        let u = l;
        l = a, a = u;
      }
      let d = document.createRange();
      d.setEnd(a.node, a.offset), d.setStart(l.node, l.offset), h.removeAllRanges(), h.addRange(d);
    }
  }
  ignoreMutation(e) {
    return !this.contentDOM && e.type != "selection";
  }
  get contentLost() {
    return this.contentDOM && this.contentDOM != this.dom && !this.dom.contains(this.contentDOM);
  }
  markDirty(e, t) {
    for (let n = 0, i = 0; i < this.children.length; i++) {
      let s = this.children[i], o = n + s.size;
      if (n == o ? e <= o && t >= n : e < o && t > n) {
        let l = n + s.border, a = o - s.border;
        if (e >= l && t <= a) {
          this.dirty = e == n || t == o ? Le : hr, e == l && t == a && (s.contentLost || s.dom.parentNode != this.contentDOM) ? s.dirty = ne : s.markDirty(e - l, t - l);
          return;
        } else
          s.dirty = s.dom == s.contentDOM && s.dom.parentNode == this.contentDOM && !s.children.length ? Le : ne;
      }
      n = o;
    }
    this.dirty = Le;
  }
  markParentsDirty() {
    let e = 1;
    for (let t = this.parent; t; t = t.parent, e++) {
      let n = e == 1 ? Le : hr;
      t.dirty < n && (t.dirty = n);
    }
  }
  get domAtom() {
    return !1;
  }
  get ignoreForCoords() {
    return !1;
  }
}
class Ci extends lt {
  constructor(e, t, n, i) {
    let s, o = t.type.toDOM;
    if (typeof o == "function" && (o = o(n, () => {
      if (!s)
        return i;
      if (s.parent)
        return s.parent.posBeforeChild(s);
    })), !t.type.spec.raw) {
      if (o.nodeType != 1) {
        let l = document.createElement("span");
        l.appendChild(o), o = l;
      }
      o.contentEditable = "false", o.classList.add("ProseMirror-widget");
    }
    super(e, [], o, null), this.widget = t, this.widget = t, s = this;
  }
  matchesWidget(e) {
    return this.dirty == Y && e.type.eq(this.widget.type);
  }
  parseRule() {
    return { ignore: !0 };
  }
  stopEvent(e) {
    let t = this.widget.spec.stopEvent;
    return t ? t(e) : !1;
  }
  ignoreMutation(e) {
    return e.type != "selection" || this.widget.spec.ignoreSelection;
  }
  destroy() {
    this.widget.type.destroy(this.dom), super.destroy();
  }
  get domAtom() {
    return !0;
  }
  get side() {
    return this.widget.type.side;
  }
}
class ll extends lt {
  constructor(e, t, n, i) {
    super(e, [], t, null), this.textDOM = n, this.text = i;
  }
  get size() {
    return this.text.length;
  }
  localPosFromDOM(e, t) {
    return e != this.textDOM ? this.posAtStart + (t ? this.size : 0) : this.posAtStart + t;
  }
  domFromPos(e) {
    return { node: this.textDOM, offset: e };
  }
  ignoreMutation(e) {
    return e.type === "characterData" && e.target.nodeValue == e.oldValue;
  }
}
class Ae extends lt {
  constructor(e, t, n, i) {
    super(e, [], n, i), this.mark = t;
  }
  static create(e, t, n, i) {
    let s = i.nodeViews[t.type.name], o = s && s(t, i, n);
    return (!o || !o.dom) && (o = oe.renderSpec(document, t.type.spec.toDOM(t, n))), new Ae(e, t, o.dom, o.contentDOM || o.dom);
  }
  parseRule() {
    return this.dirty & ne || this.mark.type.spec.reparseInView ? null : { mark: this.mark.type.name, attrs: this.mark.attrs, contentElement: this.contentDOM || void 0 };
  }
  matchesMark(e) {
    return this.dirty != ne && this.mark.eq(e);
  }
  markDirty(e, t) {
    if (super.markDirty(e, t), this.dirty != Y) {
      let n = this.parent;
      for (; !n.node; )
        n = n.parent;
      n.dirty < this.dirty && (n.dirty = this.dirty), this.dirty = Y;
    }
  }
  slice(e, t, n) {
    let i = Ae.create(this.parent, this.mark, !0, n), s = this.children, o = this.size;
    t < o && (s = pn(s, t, o, n)), e > 0 && (s = pn(s, 0, e, n));
    for (let l = 0; l < s.length; l++)
      s[l].parent = i;
    return i.children = s, i;
  }
}
class Ie extends lt {
  constructor(e, t, n, i, s, o, l, a, h) {
    super(e, [], s, o), this.node = t, this.outerDeco = n, this.innerDeco = i, this.nodeDOM = l, o && this.updateChildren(a, h);
  }
  static create(e, t, n, i, s, o) {
    let l = s.nodeViews[t.type.name], a, h = l && l(t, s, () => {
      if (!a)
        return o;
      if (a.parent)
        return a.parent.posBeforeChild(a);
    }, n, i), c = h && h.dom, f = h && h.contentDOM;
    if (t.isText) {
      if (!c)
        c = document.createTextNode(t.text);
      else if (c.nodeType != 3)
        throw new RangeError("Text must be rendered as a DOM text node");
    } else
      c || ({ dom: c, contentDOM: f } = oe.renderSpec(document, t.type.spec.toDOM(t)));
    !f && !t.isText && c.nodeName != "BR" && (c.hasAttribute("contenteditable") || (c.contentEditable = "false"), t.type.spec.draggable && (c.draggable = !0));
    let d = c;
    return c = Ti(c, n, t), h ? a = new al(e, t, n, i, c, f || null, d, h, s, o + 1) : t.isText ? new zt(e, t, n, i, c, d, s) : new Ie(e, t, n, i, c, f || null, d, s, o + 1);
  }
  parseRule() {
    if (this.node.type.spec.reparseInView)
      return null;
    let e = { node: this.node.type.name, attrs: this.node.attrs };
    if (this.node.type.whitespace == "pre" && (e.preserveWhitespace = "full"), !this.contentDOM)
      e.getContent = () => this.node.content;
    else if (!this.contentLost)
      e.contentElement = this.contentDOM;
    else {
      for (let t = this.children.length - 1; t >= 0; t--) {
        let n = this.children[t];
        if (this.dom.contains(n.dom.parentNode)) {
          e.contentElement = n.dom.parentNode;
          break;
        }
      }
      e.contentElement || (e.getContent = () => g.empty);
    }
    return e;
  }
  matchesNode(e, t, n) {
    return this.dirty == Y && e.eq(this.node) && un(t, this.outerDeco) && n.eq(this.innerDeco);
  }
  get size() {
    return this.node.nodeSize;
  }
  get border() {
    return this.node.isLeaf ? 0 : 1;
  }
  updateChildren(e, t) {
    let n = this.node.inlineContent, i = t, s = e.composing ? this.localCompositionInfo(e, t) : null, o = s && s.pos > -1 ? s : null, l = s && s.pos < 0, a = new cl(this, o && o.node, e);
    ul(this.node, this.innerDeco, (h, c, f) => {
      h.spec.marks ? a.syncToMarks(h.spec.marks, n, e) : h.type.side >= 0 && !f && a.syncToMarks(c == this.node.childCount ? M.none : this.node.child(c).marks, n, e), a.placeWidget(h, e, i);
    }, (h, c, f, d) => {
      a.syncToMarks(h.marks, n, e);
      let u;
      a.findNodeMatch(h, c, f, d) || l && e.state.selection.from > i && e.state.selection.to < i + h.nodeSize && (u = a.findIndexWithChild(s.node)) > -1 && a.updateNodeAt(h, c, f, u, e) || a.updateNextNode(h, c, f, e, d) || a.addNode(h, c, f, e, i), i += h.nodeSize;
    }), a.syncToMarks([], n, e), this.node.isTextblock && a.addTextblockHacks(), a.destroyRest(), (a.changed || this.dirty == Le) && (o && this.protectLocalComposition(e, o), Ni(this.contentDOM, this.children, e), Ue && pl(this.dom));
  }
  localCompositionInfo(e, t) {
    let { from: n, to: i } = e.state.selection;
    if (!(e.state.selection instanceof O) || n < t || i > t + this.node.content.size)
      return null;
    let s = e.domSelectionRange(), o = ml(s.focusNode, s.focusOffset);
    if (!o || !this.dom.contains(o.parentNode))
      return null;
    if (this.node.inlineContent) {
      let l = o.nodeValue, a = gl(this.node.content, l, n - t, i - t);
      return a < 0 ? null : { node: o, pos: a, text: l };
    } else
      return { node: o, pos: -1, text: "" };
  }
  protectLocalComposition(e, { node: t, pos: n, text: i }) {
    if (this.getDesc(t))
      return;
    let s = t;
    for (; s.parentNode != this.contentDOM; s = s.parentNode) {
      for (; s.previousSibling; )
        s.parentNode.removeChild(s.previousSibling);
      for (; s.nextSibling; )
        s.parentNode.removeChild(s.nextSibling);
      s.pmViewDesc && (s.pmViewDesc = void 0);
    }
    let o = new ll(this, s, t, i);
    e.input.compositionNodes.push(o), this.children = pn(this.children, n, n + i.length, e, o);
  }
  update(e, t, n, i) {
    return this.dirty == ne || !e.sameMarkup(this.node) ? !1 : (this.updateInner(e, t, n, i), !0);
  }
  updateInner(e, t, n, i) {
    this.updateOuterDeco(t), this.node = e, this.innerDeco = n, this.contentDOM && this.updateChildren(i, this.posAtStart), this.dirty = Y;
  }
  updateOuterDeco(e) {
    if (un(e, this.outerDeco))
      return;
    let t = this.nodeDOM.nodeType != 1, n = this.dom;
    this.dom = wi(this.dom, this.nodeDOM, dn(this.outerDeco, this.node, t), dn(e, this.node, t)), this.dom != n && (n.pmViewDesc = void 0, this.dom.pmViewDesc = this), this.outerDeco = e;
  }
  selectNode() {
    this.nodeDOM.nodeType == 1 && this.nodeDOM.classList.add("ProseMirror-selectednode"), (this.contentDOM || !this.node.type.spec.draggable) && (this.dom.draggable = !0);
  }
  deselectNode() {
    this.nodeDOM.nodeType == 1 && this.nodeDOM.classList.remove("ProseMirror-selectednode"), (this.contentDOM || !this.node.type.spec.draggable) && this.dom.removeAttribute("draggable");
  }
  get domAtom() {
    return this.node.isAtom;
  }
}
function cr(r, e, t, n, i) {
  return Ti(n, e, r), new Ie(void 0, r, e, t, n, n, n, i, 0);
}
class zt extends Ie {
  constructor(e, t, n, i, s, o, l) {
    super(e, t, n, i, s, null, o, l, 0);
  }
  parseRule() {
    let e = this.nodeDOM.parentNode;
    for (; e && e != this.dom && !e.pmIsDeco; )
      e = e.parentNode;
    return { skip: e || !0 };
  }
  update(e, t, n, i) {
    return this.dirty == ne || this.dirty != Y && !this.inParent() || !e.sameMarkup(this.node) ? !1 : (this.updateOuterDeco(t), (this.dirty != Y || e.text != this.node.text) && e.text != this.nodeDOM.nodeValue && (this.nodeDOM.nodeValue = e.text, i.trackWrites == this.nodeDOM && (i.trackWrites = null)), this.node = e, this.dirty = Y, !0);
  }
  inParent() {
    let e = this.parent.contentDOM;
    for (let t = this.nodeDOM; t; t = t.parentNode)
      if (t == e)
        return !0;
    return !1;
  }
  domFromPos(e) {
    return { node: this.nodeDOM, offset: e };
  }
  localPosFromDOM(e, t, n) {
    return e == this.nodeDOM ? this.posAtStart + Math.min(t, this.node.text.length) : super.localPosFromDOM(e, t, n);
  }
  ignoreMutation(e) {
    return e.type != "characterData" && e.type != "selection";
  }
  slice(e, t, n) {
    let i = this.node.cut(e, t), s = document.createTextNode(i.text);
    return new zt(this.parent, i, this.outerDeco, this.innerDeco, s, s, n);
  }
  markDirty(e, t) {
    super.markDirty(e, t), this.dom != this.nodeDOM && (e == 0 || t == this.nodeDOM.nodeValue.length) && (this.dirty = ne);
  }
  get domAtom() {
    return !1;
  }
}
class Oi extends lt {
  parseRule() {
    return { ignore: !0 };
  }
  matchesHack(e) {
    return this.dirty == Y && this.dom.nodeName == e;
  }
  get domAtom() {
    return !0;
  }
  get ignoreForCoords() {
    return this.dom.nodeName == "IMG";
  }
}
class al extends Ie {
  constructor(e, t, n, i, s, o, l, a, h, c) {
    super(e, t, n, i, s, o, l, h, c), this.spec = a;
  }
  update(e, t, n, i) {
    if (this.dirty == ne)
      return !1;
    if (this.spec.update) {
      let s = this.spec.update(e, t, n);
      return s && this.updateInner(e, t, n, i), s;
    } else
      return !this.contentDOM && !e.isLeaf ? !1 : super.update(e, t, n, i);
  }
  selectNode() {
    this.spec.selectNode ? this.spec.selectNode() : super.selectNode();
  }
  deselectNode() {
    this.spec.deselectNode ? this.spec.deselectNode() : super.deselectNode();
  }
  setSelection(e, t, n, i) {
    this.spec.setSelection ? this.spec.setSelection(e, t, n) : super.setSelection(e, t, n, i);
  }
  destroy() {
    this.spec.destroy && this.spec.destroy(), super.destroy();
  }
  stopEvent(e) {
    return this.spec.stopEvent ? this.spec.stopEvent(e) : !1;
  }
  ignoreMutation(e) {
    return this.spec.ignoreMutation ? this.spec.ignoreMutation(e) : super.ignoreMutation(e);
  }
}
function Ni(r, e, t) {
  let n = r.firstChild, i = !1;
  for (let s = 0; s < e.length; s++) {
    let o = e[s], l = o.dom;
    if (l.parentNode == r) {
      for (; l != n; )
        n = fr(n), i = !0;
      n = n.nextSibling;
    } else
      i = !0, r.insertBefore(l, n);
    if (o instanceof Ae) {
      let a = n ? n.previousSibling : r.lastChild;
      Ni(o.contentDOM, o.children, t), n = a ? a.nextSibling : r.firstChild;
    }
  }
  for (; n; )
    n = fr(n), i = !0;
  i && t.trackWrites == r && (t.trackWrites = null);
}
const tt = function(r) {
  r && (this.nodeName = r);
};
tt.prototype = /* @__PURE__ */ Object.create(null);
const Ce = [new tt()];
function dn(r, e, t) {
  if (r.length == 0)
    return Ce;
  let n = t ? Ce[0] : new tt(), i = [n];
  for (let s = 0; s < r.length; s++) {
    let o = r[s].type.attrs;
    if (!!o) {
      o.nodeName && i.push(n = new tt(o.nodeName));
      for (let l in o) {
        let a = o[l];
        a != null && (t && i.length == 1 && i.push(n = new tt(e.isInline ? "span" : "div")), l == "class" ? n.class = (n.class ? n.class + " " : "") + a : l == "style" ? n.style = (n.style ? n.style + ";" : "") + a : l != "nodeName" && (n[l] = a));
      }
    }
  }
  return i;
}
function wi(r, e, t, n) {
  if (t == Ce && n == Ce)
    return e;
  let i = e;
  for (let s = 0; s < n.length; s++) {
    let o = n[s], l = t[s];
    if (s) {
      let a;
      l && l.nodeName == o.nodeName && i != r && (a = i.parentNode) && a.nodeName.toLowerCase() == o.nodeName || (a = document.createElement(o.nodeName), a.pmIsDeco = !0, a.appendChild(i), l = Ce[0]), i = a;
    }
    hl(i, l || Ce[0], o);
  }
  return i;
}
function hl(r, e, t) {
  for (let n in e)
    n != "class" && n != "style" && n != "nodeName" && !(n in t) && r.removeAttribute(n);
  for (let n in t)
    n != "class" && n != "style" && n != "nodeName" && t[n] != e[n] && r.setAttribute(n, t[n]);
  if (e.class != t.class) {
    let n = e.class ? e.class.split(" ").filter(Boolean) : [], i = t.class ? t.class.split(" ").filter(Boolean) : [];
    for (let s = 0; s < n.length; s++)
      i.indexOf(n[s]) == -1 && r.classList.remove(n[s]);
    for (let s = 0; s < i.length; s++)
      n.indexOf(i[s]) == -1 && r.classList.add(i[s]);
    r.classList.length == 0 && r.removeAttribute("class");
  }
  if (e.style != t.style) {
    if (e.style) {
      let n = /\s*([\w\-\xa1-\uffff]+)\s*:(?:"(?:\\.|[^"])*"|'(?:\\.|[^'])*'|\(.*?\)|[^;])*/g, i;
      for (; i = n.exec(e.style); )
        r.style.removeProperty(i[1]);
    }
    t.style && (r.style.cssText += t.style);
  }
}
function Ti(r, e, t) {
  return wi(r, r, Ce, dn(e, t, r.nodeType != 1));
}
function un(r, e) {
  if (r.length != e.length)
    return !1;
  for (let t = 0; t < r.length; t++)
    if (!r[t].type.eq(e[t].type))
      return !1;
  return !0;
}
function fr(r) {
  let e = r.nextSibling;
  return r.parentNode.removeChild(r), e;
}
class cl {
  constructor(e, t, n) {
    this.lock = t, this.view = n, this.index = 0, this.stack = [], this.changed = !1, this.top = e, this.preMatch = fl(e.node.content, e);
  }
  destroyBetween(e, t) {
    if (e != t) {
      for (let n = e; n < t; n++)
        this.top.children[n].destroy();
      this.top.children.splice(e, t - e), this.changed = !0;
    }
  }
  destroyRest() {
    this.destroyBetween(this.index, this.top.children.length);
  }
  syncToMarks(e, t, n) {
    let i = 0, s = this.stack.length >> 1, o = Math.min(s, e.length);
    for (; i < o && (i == s - 1 ? this.top : this.stack[i + 1 << 1]).matchesMark(e[i]) && e[i].type.spec.spanning !== !1; )
      i++;
    for (; i < s; )
      this.destroyRest(), this.top.dirty = Y, this.index = this.stack.pop(), this.top = this.stack.pop(), s--;
    for (; s < e.length; ) {
      this.stack.push(this.top, this.index + 1);
      let l = -1;
      for (let a = this.index; a < Math.min(this.index + 3, this.top.children.length); a++)
        if (this.top.children[a].matchesMark(e[s])) {
          l = a;
          break;
        }
      if (l > -1)
        l > this.index && (this.changed = !0, this.destroyBetween(this.index, l)), this.top = this.top.children[this.index];
      else {
        let a = Ae.create(this.top, e[s], t, n);
        this.top.children.splice(this.index, 0, a), this.top = a, this.changed = !0;
      }
      this.index = 0, s++;
    }
  }
  findNodeMatch(e, t, n, i) {
    let s = -1, o;
    if (i >= this.preMatch.index && (o = this.preMatch.matches[i - this.preMatch.index]).parent == this.top && o.matchesNode(e, t, n))
      s = this.top.children.indexOf(o, this.index);
    else
      for (let l = this.index, a = Math.min(this.top.children.length, l + 5); l < a; l++) {
        let h = this.top.children[l];
        if (h.matchesNode(e, t, n) && !this.preMatch.matched.has(h)) {
          s = l;
          break;
        }
      }
    return s < 0 ? !1 : (this.destroyBetween(this.index, s), this.index++, !0);
  }
  updateNodeAt(e, t, n, i, s) {
    let o = this.top.children[i];
    return o.dirty == ne && o.dom == o.contentDOM && (o.dirty = Le), o.update(e, t, n, s) ? (this.destroyBetween(this.index, i), this.index++, !0) : !1;
  }
  findIndexWithChild(e) {
    for (; ; ) {
      let t = e.parentNode;
      if (!t)
        return -1;
      if (t == this.top.contentDOM) {
        let n = e.pmViewDesc;
        if (n) {
          for (let i = this.index; i < this.top.children.length; i++)
            if (this.top.children[i] == n)
              return i;
        }
        return -1;
      }
      e = t;
    }
  }
  updateNextNode(e, t, n, i, s) {
    for (let o = this.index; o < this.top.children.length; o++) {
      let l = this.top.children[o];
      if (l instanceof Ie) {
        let a = this.preMatch.matched.get(l);
        if (a != null && a != s)
          return !1;
        let h = l.dom;
        if (!(this.lock && (h == this.lock || h.nodeType == 1 && h.contains(this.lock.parentNode)) && !(e.isText && l.node && l.node.isText && l.nodeDOM.nodeValue == e.text && l.dirty != ne && un(t, l.outerDeco))) && l.update(e, t, n, i))
          return this.destroyBetween(this.index, o), l.dom != h && (this.changed = !0), this.index++, !0;
        break;
      }
    }
    return !1;
  }
  addNode(e, t, n, i, s) {
    this.top.children.splice(this.index++, 0, Ie.create(this.top, e, t, n, i, s)), this.changed = !0;
  }
  placeWidget(e, t, n) {
    let i = this.index < this.top.children.length ? this.top.children[this.index] : null;
    if (i && i.matchesWidget(e) && (e == i.widget || !i.widget.type.toDOM.parentNode))
      this.index++;
    else {
      let s = new Ci(this.top, e, t, n);
      this.top.children.splice(this.index++, 0, s), this.changed = !0;
    }
  }
  addTextblockHacks() {
    let e = this.top.children[this.index - 1], t = this.top;
    for (; e instanceof Ae; )
      t = e, e = t.children[t.children.length - 1];
    (!e || !(e instanceof zt) || /\n$/.test(e.node.text) || this.view.requiresGeckoHackNode && /\s$/.test(e.node.text)) && ((R || v) && e && e.dom.contentEditable == "false" && this.addHackNode("IMG", t), this.addHackNode("BR", this.top));
  }
  addHackNode(e, t) {
    if (t == this.top && this.index < t.children.length && t.children[this.index].matchesHack(e))
      this.index++;
    else {
      let n = document.createElement(e);
      e == "IMG" && (n.className = "ProseMirror-separator", n.alt = ""), e == "BR" && (n.className = "ProseMirror-trailingBreak");
      let i = new Oi(this.top, [], n, null);
      t != this.top ? t.children.push(i) : t.children.splice(this.index++, 0, i), this.changed = !0;
    }
  }
}
function fl(r, e) {
  let t = e, n = t.children.length, i = r.childCount, s = /* @__PURE__ */ new Map(), o = [];
  e:
    for (; i > 0; ) {
      let l;
      for (; ; )
        if (n) {
          let h = t.children[n - 1];
          if (h instanceof Ae)
            t = h, n = h.children.length;
          else {
            l = h, n--;
            break;
          }
        } else {
          if (t == e)
            break e;
          n = t.parent.children.indexOf(t), t = t.parent;
        }
      let a = l.node;
      if (!!a) {
        if (a != r.child(i - 1))
          break;
        --i, s.set(l, i), o.push(l);
      }
    }
  return { index: i, matched: s, matches: o.reverse() };
}
function dl(r, e) {
  return r.type.side - e.type.side;
}
function ul(r, e, t, n) {
  let i = e.locals(r), s = 0;
  if (i.length == 0) {
    for (let h = 0; h < r.childCount; h++) {
      let c = r.child(h);
      n(c, i, e.forChild(s, c), h), s += c.nodeSize;
    }
    return;
  }
  let o = 0, l = [], a = null;
  for (let h = 0; ; ) {
    if (o < i.length && i[o].to == s) {
      let p = i[o++], m;
      for (; o < i.length && i[o].to == s; )
        (m || (m = [p])).push(i[o++]);
      if (m) {
        m.sort(dl);
        for (let k = 0; k < m.length; k++)
          t(m[k], h, !!a);
      } else
        t(p, h, !!a);
    }
    let c, f;
    if (a)
      f = -1, c = a, a = null;
    else if (h < r.childCount)
      f = h, c = r.child(h++);
    else
      break;
    for (let p = 0; p < l.length; p++)
      l[p].to <= s && l.splice(p--, 1);
    for (; o < i.length && i[o].from <= s && i[o].to > s; )
      l.push(i[o++]);
    let d = s + c.nodeSize;
    if (c.isText) {
      let p = d;
      o < i.length && i[o].from < p && (p = i[o].from);
      for (let m = 0; m < l.length; m++)
        l[m].to < p && (p = l[m].to);
      p < d && (a = c.cut(p - s), c = c.cut(0, p - s), d = p, f = -1);
    }
    let u = c.isInline && !c.isLeaf ? l.filter((p) => !p.inline) : l.slice();
    n(c, u, e.forChild(s, c), f), s = d;
  }
}
function pl(r) {
  if (r.nodeName == "UL" || r.nodeName == "OL") {
    let e = r.style.cssText;
    r.style.cssText = e + "; list-style: square !important", window.getComputedStyle(r).listStyle, r.style.cssText = e;
  }
}
function ml(r, e) {
  for (; ; ) {
    if (r.nodeType == 3)
      return r;
    if (r.nodeType == 1 && e > 0) {
      if (r.childNodes.length > e && r.childNodes[e].nodeType == 3)
        return r.childNodes[e];
      r = r.childNodes[e - 1], e = Q(r);
    } else if (r.nodeType == 1 && e < r.childNodes.length)
      r = r.childNodes[e], e = 0;
    else
      return null;
  }
}
function gl(r, e, t, n) {
  for (let i = 0, s = 0; i < r.childCount && s <= n; ) {
    let o = r.child(i++), l = s;
    if (s += o.nodeSize, !o.isText)
      continue;
    let a = o.text;
    for (; i < r.childCount; ) {
      let h = r.child(i++);
      if (s += h.nodeSize, !h.isText)
        break;
      a += h.text;
    }
    if (s >= t) {
      let h = l < n ? a.lastIndexOf(e, n - l - 1) : -1;
      if (h >= 0 && h + e.length + l >= t)
        return l + h;
      if (t == n && a.length >= n + e.length - l && a.slice(n - l, n - l + e.length) == e)
        return n;
    }
  }
  return -1;
}
function pn(r, e, t, n, i) {
  let s = [];
  for (let o = 0, l = 0; o < r.length; o++) {
    let a = r[o], h = l, c = l += a.size;
    h >= t || c <= e ? s.push(a) : (h < e && s.push(a.slice(0, e - h, n)), i && (s.push(i), i = void 0), c > t && s.push(a.slice(t - h, a.size, n)));
  }
  return s;
}
function Dn(r, e = null) {
  let t = r.domSelectionRange(), n = r.state.doc;
  if (!t.focusNode)
    return null;
  let i = r.docView.nearestDesc(t.focusNode), s = i && i.size == 0, o = r.docView.posFromDOM(t.focusNode, t.focusOffset, 1);
  if (o < 0)
    return null;
  let l = n.resolve(o), a, h;
  if (Rt(t)) {
    for (a = l; i && !i.node; )
      i = i.parent;
    let c = i.node;
    if (i && c.isAtom && x.isSelectable(c) && i.parent && !(c.isInline && Wo(t.focusNode, t.focusOffset, i.dom))) {
      let f = i.posBefore;
      h = new x(o == f ? l : n.resolve(f));
    }
  } else {
    let c = r.docView.posFromDOM(t.anchorNode, t.anchorOffset, 1);
    if (c < 0)
      return null;
    a = n.resolve(c);
  }
  if (!h) {
    let c = e == "pointer" || r.state.selection.head < l.pos && !s ? 1 : -1;
    h = En(r, a, l, c);
  }
  return h;
}
function Di(r) {
  return r.editable ? r.hasFocus() : Ai(r) && document.activeElement && document.activeElement.contains(r.dom);
}
function le(r, e = !1) {
  let t = r.state.selection;
  if (Ei(r, t), !!Di(r)) {
    if (!e && r.input.mouseDown && r.input.mouseDown.allowDefault && v) {
      let n = r.domSelectionRange(), i = r.domObserver.currentSelection;
      if (n.anchorNode && i.anchorNode && Ee(n.anchorNode, n.anchorOffset, i.anchorNode, i.anchorOffset)) {
        r.input.mouseDown.delayedSelectionSync = !0, r.domObserver.setCurSelection();
        return;
      }
    }
    if (r.domObserver.disconnectSelection(), r.cursorWrapper)
      kl(r);
    else {
      let { anchor: n, head: i } = t, s, o;
      dr && !(t instanceof O) && (t.$from.parent.inlineContent || (s = ur(r, t.from)), !t.empty && !t.$from.parent.inlineContent && (o = ur(r, t.to))), r.docView.setSelection(n, i, r.root, e), dr && (s && pr(s), o && pr(o)), t.visible ? r.dom.classList.remove("ProseMirror-hideselection") : (r.dom.classList.add("ProseMirror-hideselection"), "onselectionchange" in document && yl(r));
    }
    r.domObserver.setCurSelection(), r.domObserver.connectSelection();
  }
}
const dr = R || v && Ho < 63;
function ur(r, e) {
  let { node: t, offset: n } = r.docView.domFromPos(e, 0), i = n < t.childNodes.length ? t.childNodes[n] : null, s = n ? t.childNodes[n - 1] : null;
  if (R && i && i.contentEditable == "false")
    return en(i);
  if ((!i || i.contentEditable == "false") && (!s || s.contentEditable == "false")) {
    if (i)
      return en(i);
    if (s)
      return en(s);
  }
}
function en(r) {
  return r.contentEditable = "true", R && r.draggable && (r.draggable = !1, r.wasDraggable = !0), r;
}
function pr(r) {
  r.contentEditable = "false", r.wasDraggable && (r.draggable = !0, r.wasDraggable = null);
}
function yl(r) {
  let e = r.dom.ownerDocument;
  e.removeEventListener("selectionchange", r.input.hideSelectionGuard);
  let t = r.domSelectionRange(), n = t.anchorNode, i = t.anchorOffset;
  e.addEventListener("selectionchange", r.input.hideSelectionGuard = () => {
    (t.anchorNode != n || t.anchorOffset != i) && (e.removeEventListener("selectionchange", r.input.hideSelectionGuard), setTimeout(() => {
      (!Di(r) || r.state.selection.visible) && r.dom.classList.remove("ProseMirror-hideselection");
    }, 20));
  });
}
function kl(r) {
  let e = r.domSelection(), t = document.createRange(), n = r.cursorWrapper.dom, i = n.nodeName == "IMG";
  i ? t.setEnd(n.parentNode, J(n) + 1) : t.setEnd(n, 0), t.collapse(!1), e.removeAllRanges(), e.addRange(t), !i && !r.state.selection.visible && F && ge <= 11 && (n.disabled = !0, n.disabled = !1);
}
function Ei(r, e) {
  if (e instanceof x) {
    let t = r.docView.descAt(e.from);
    t != r.lastSelectedViewDesc && (mr(r), t && t.selectNode(), r.lastSelectedViewDesc = t);
  } else
    mr(r);
}
function mr(r) {
  r.lastSelectedViewDesc && (r.lastSelectedViewDesc.parent && r.lastSelectedViewDesc.deselectNode(), r.lastSelectedViewDesc = void 0);
}
function En(r, e, t, n) {
  return r.someProp("createSelectionBetween", (i) => i(r, e, t)) || O.between(e, t, n);
}
function gr(r) {
  return r.editable && !r.hasFocus() ? !1 : Ai(r);
}
function Ai(r) {
  let e = r.domSelectionRange();
  if (!e.anchorNode)
    return !1;
  try {
    return r.dom.contains(e.anchorNode.nodeType == 3 ? e.anchorNode.parentNode : e.anchorNode) && (r.editable || r.dom.contains(e.focusNode.nodeType == 3 ? e.focusNode.parentNode : e.focusNode));
  } catch {
    return !1;
  }
}
function xl(r) {
  let e = r.docView.domFromPos(r.state.selection.anchor, 0), t = r.domSelectionRange();
  return Ee(e.node, e.offset, t.anchorNode, t.anchorOffset);
}
function mn(r, e) {
  let { $anchor: t, $head: n } = r.selection, i = e > 0 ? t.max(n) : t.min(n), s = i.parent.inlineContent ? i.depth ? r.doc.resolve(e > 0 ? i.after() : i.before()) : null : i;
  return s && C.findFrom(s, e);
}
function Me(r, e) {
  return r.dispatch(r.state.tr.setSelection(e).scrollIntoView()), !0;
}
function yr(r, e, t) {
  let n = r.state.selection;
  if (n instanceof O) {
    if (!n.empty || t.indexOf("s") > -1)
      return !1;
    if (r.endOfTextblock(e > 0 ? "right" : "left")) {
      let i = mn(r.state, e);
      return i && i instanceof x ? Me(r, i) : !1;
    } else if (!(H && t.indexOf("m") > -1)) {
      let i = n.$head, s = i.textOffset ? null : e < 0 ? i.nodeBefore : i.nodeAfter, o;
      if (!s || s.isText)
        return !1;
      let l = e < 0 ? i.pos - s.nodeSize : i.pos;
      return s.isAtom || (o = r.docView.descAt(l)) && !o.contentDOM ? x.isSelectable(s) ? Me(r, new x(e < 0 ? r.state.doc.resolve(i.pos - s.nodeSize) : i)) : Pt ? Me(r, new O(r.state.doc.resolve(e < 0 ? l : l + s.nodeSize))) : !1 : !1;
    }
  } else {
    if (n instanceof x && n.node.isInline)
      return Me(r, new O(e > 0 ? n.$to : n.$from));
    {
      let i = mn(r.state, e);
      return i ? Me(r, i) : !1;
    }
  }
}
function wt(r) {
  return r.nodeType == 3 ? r.nodeValue.length : r.childNodes.length;
}
function nt(r) {
  let e = r.pmViewDesc;
  return e && e.size == 0 && (r.nextSibling || r.nodeName != "BR");
}
function tn(r) {
  let e = r.domSelectionRange(), t = e.focusNode, n = e.focusOffset;
  if (!t)
    return;
  let i, s, o = !1;
  for (X && t.nodeType == 1 && n < wt(t) && nt(t.childNodes[n]) && (o = !0); ; )
    if (n > 0) {
      if (t.nodeType != 1)
        break;
      {
        let l = t.childNodes[n - 1];
        if (nt(l))
          i = t, s = --n;
        else if (l.nodeType == 3)
          t = l, n = t.nodeValue.length;
        else
          break;
      }
    } else {
      if (Ii(t))
        break;
      {
        let l = t.previousSibling;
        for (; l && nt(l); )
          i = t.parentNode, s = J(l), l = l.previousSibling;
        if (l)
          t = l, n = wt(t);
        else {
          if (t = t.parentNode, t == r.dom)
            break;
          n = 0;
        }
      }
    }
  o ? gn(r, t, n) : i && gn(r, i, s);
}
function nn(r) {
  let e = r.domSelectionRange(), t = e.focusNode, n = e.focusOffset;
  if (!t)
    return;
  let i = wt(t), s, o;
  for (; ; )
    if (n < i) {
      if (t.nodeType != 1)
        break;
      let l = t.childNodes[n];
      if (nt(l))
        s = t, o = ++n;
      else
        break;
    } else {
      if (Ii(t))
        break;
      {
        let l = t.nextSibling;
        for (; l && nt(l); )
          s = l.parentNode, o = J(l) + 1, l = l.nextSibling;
        if (l)
          t = l, n = 0, i = wt(t);
        else {
          if (t = t.parentNode, t == r.dom)
            break;
          n = i = 0;
        }
      }
    }
  s && gn(r, s, o);
}
function Ii(r) {
  let e = r.pmViewDesc;
  return e && e.node && e.node.isBlock;
}
function gn(r, e, t) {
  let n = r.domSelection();
  if (Rt(n)) {
    let s = document.createRange();
    s.setEnd(e, t), s.setStart(e, t), n.removeAllRanges(), n.addRange(s);
  } else
    n.extend && n.extend(e, t);
  r.domObserver.setCurSelection();
  let { state: i } = r;
  setTimeout(() => {
    r.state == i && le(r);
  }, 50);
}
function kr(r, e, t) {
  let n = r.state.selection;
  if (n instanceof O && !n.empty || t.indexOf("s") > -1 || H && t.indexOf("m") > -1)
    return !1;
  let { $from: i, $to: s } = n;
  if (!i.parent.inlineContent || r.endOfTextblock(e < 0 ? "up" : "down")) {
    let o = mn(r.state, e);
    if (o && o instanceof x)
      return Me(r, o);
  }
  if (!i.parent.inlineContent) {
    let o = e < 0 ? i : s, l = n instanceof q ? C.near(o, e) : C.findFrom(o, e);
    return l ? Me(r, l) : !1;
  }
  return !1;
}
function xr(r, e) {
  if (!(r.state.selection instanceof O))
    return !0;
  let { $head: t, $anchor: n, empty: i } = r.state.selection;
  if (!t.sameParent(n))
    return !0;
  if (!i)
    return !1;
  if (r.endOfTextblock(e > 0 ? "forward" : "backward"))
    return !0;
  let s = !t.textOffset && (e < 0 ? t.nodeBefore : t.nodeAfter);
  if (s && !s.isText) {
    let o = r.state.tr;
    return e < 0 ? o.delete(t.pos - s.nodeSize, t.pos) : o.delete(t.pos, t.pos + s.nodeSize), r.dispatch(o), !0;
  }
  return !1;
}
function Sr(r, e, t) {
  r.domObserver.stop(), e.contentEditable = t, r.domObserver.start();
}
function Sl(r) {
  if (!R || r.state.selection.$head.parentOffset > 0)
    return !1;
  let { focusNode: e, focusOffset: t } = r.domSelectionRange();
  if (e && e.nodeType == 1 && t == 0 && e.firstChild && e.firstChild.contentEditable == "false") {
    let n = e.firstChild;
    Sr(r, n, "true"), setTimeout(() => Sr(r, n, "false"), 20);
  }
  return !1;
}
function bl(r) {
  let e = "";
  return r.ctrlKey && (e += "c"), r.metaKey && (e += "m"), r.altKey && (e += "a"), r.shiftKey && (e += "s"), e;
}
function Ml(r, e) {
  let t = e.keyCode, n = bl(e);
  return t == 8 || H && t == 72 && n == "c" ? xr(r, -1) || tn(r) : t == 46 || H && t == 68 && n == "c" ? xr(r, 1) || nn(r) : t == 13 || t == 27 ? !0 : t == 37 || H && t == 66 && n == "c" ? yr(r, -1, n) || tn(r) : t == 39 || H && t == 70 && n == "c" ? yr(r, 1, n) || nn(r) : t == 38 || H && t == 80 && n == "c" ? kr(r, -1, n) || tn(r) : t == 40 || H && t == 78 && n == "c" ? Sl(r) || kr(r, 1, n) || nn(r) : n == (H ? "m" : "c") && (t == 66 || t == 73 || t == 89 || t == 90);
}
function Ri(r, e) {
  r.someProp("transformCopied", (u) => {
    e = u(e, r);
  });
  let t = [], { content: n, openStart: i, openEnd: s } = e;
  for (; i > 1 && s > 1 && n.childCount == 1 && n.firstChild.childCount == 1; ) {
    i--, s--;
    let u = n.firstChild;
    t.push(u.type.name, u.attrs != u.type.defaultAttrs ? u.attrs : null), n = u.content;
  }
  let o = r.someProp("clipboardSerializer") || oe.fromSchema(r.state.schema), l = Vi(), a = l.createElement("div");
  a.appendChild(o.serializeFragment(n, { document: l }));
  let h = a.firstChild, c, f = 0;
  for (; h && h.nodeType == 1 && (c = Fi[h.nodeName.toLowerCase()]); ) {
    for (let u = c.length - 1; u >= 0; u--) {
      let p = l.createElement(c[u]);
      for (; a.firstChild; )
        p.appendChild(a.firstChild);
      a.appendChild(p), f++;
    }
    h = a.firstChild;
  }
  h && h.nodeType == 1 && h.setAttribute("data-pm-slice", `${i} ${s}${f ? ` -${f}` : ""} ${JSON.stringify(t)}`);
  let d = r.someProp("clipboardTextSerializer", (u) => u(e, r)) || e.content.textBetween(0, e.content.size, `

`);
  return { dom: a, text: d };
}
function Pi(r, e, t, n, i) {
  let s = i.parent.type.spec.code, o, l;
  if (!t && !e)
    return null;
  let a = e && (n || s || !t);
  if (a) {
    if (r.someProp("transformPastedText", (d) => {
      e = d(e, s || n, r);
    }), s)
      return e ? new y(g.from(r.state.schema.text(e.replace(/\r\n?/g, `
`))), 0, 0) : y.empty;
    let f = r.someProp("clipboardTextParser", (d) => d(e, i, n, r));
    if (f)
      l = f;
    else {
      let d = i.marks(), { schema: u } = r.state, p = oe.fromSchema(u);
      o = document.createElement("div"), e.split(/(?:\r\n?|\n)+/).forEach((m) => {
        let k = o.appendChild(document.createElement("p"));
        m && k.appendChild(p.serializeNode(u.text(m, d)));
      });
    }
  } else
    r.someProp("transformPastedHTML", (f) => {
      t = f(t, r);
    }), o = Nl(t), Pt && wl(o);
  let h = o && o.querySelector("[data-pm-slice]"), c = h && /^(\d+) (\d+)(?: -(\d+))? (.*)/.exec(h.getAttribute("data-pm-slice") || "");
  if (c && c[3])
    for (let f = +c[3]; f > 0; f--) {
      let d = o.firstChild;
      for (; d && d.nodeType != 1; )
        d = d.nextSibling;
      if (!d)
        break;
      o = d;
    }
  if (l || (l = (r.someProp("clipboardParser") || r.someProp("domParser") || it.fromSchema(r.state.schema)).parseSlice(o, {
    preserveWhitespace: !!(a || c),
    context: i,
    ruleFromNode(d) {
      return d.nodeName == "BR" && !d.nextSibling && d.parentNode && !Cl.test(d.parentNode.nodeName) ? { ignore: !0 } : null;
    }
  })), c)
    l = Tl(br(l, +c[1], +c[2]), c[4]);
  else if (l = y.maxOpen(Ol(l.content, i), !0), l.openStart || l.openEnd) {
    let f = 0, d = 0;
    for (let u = l.content.firstChild; f < l.openStart && !u.type.spec.isolating; f++, u = u.firstChild)
      ;
    for (let u = l.content.lastChild; d < l.openEnd && !u.type.spec.isolating; d++, u = u.lastChild)
      ;
    l = br(l, f, d);
  }
  return r.someProp("transformPasted", (f) => {
    l = f(l, r);
  }), l;
}
const Cl = /^(a|abbr|acronym|b|cite|code|del|em|i|ins|kbd|label|output|q|ruby|s|samp|span|strong|sub|sup|time|u|tt|var)$/i;
function Ol(r, e) {
  if (r.childCount < 2)
    return r;
  for (let t = e.depth; t >= 0; t--) {
    let i = e.node(t).contentMatchAt(e.index(t)), s, o = [];
    if (r.forEach((l) => {
      if (!o)
        return;
      let a = i.findWrapping(l.type), h;
      if (!a)
        return o = null;
      if (h = o.length && s.length && Bi(a, s, l, o[o.length - 1], 0))
        o[o.length - 1] = h;
      else {
        o.length && (o[o.length - 1] = vi(o[o.length - 1], s.length));
        let c = zi(l, a);
        o.push(c), i = i.matchType(c.type), s = a;
      }
    }), o)
      return g.from(o);
  }
  return r;
}
function zi(r, e, t = 0) {
  for (let n = e.length - 1; n >= t; n--)
    r = e[n].create(null, g.from(r));
  return r;
}
function Bi(r, e, t, n, i) {
  if (i < r.length && i < e.length && r[i] == e[i]) {
    let s = Bi(r, e, t, n.lastChild, i + 1);
    if (s)
      return n.copy(n.content.replaceChild(n.childCount - 1, s));
    if (n.contentMatchAt(n.childCount).matchType(i == r.length - 1 ? t.type : r[i + 1]))
      return n.copy(n.content.append(g.from(zi(t, r, i + 1))));
  }
}
function vi(r, e) {
  if (e == 0)
    return r;
  let t = r.content.replaceChild(r.childCount - 1, vi(r.lastChild, e - 1)), n = r.contentMatchAt(r.childCount).fillBefore(g.empty, !0);
  return r.copy(t.append(n));
}
function yn(r, e, t, n, i, s) {
  let o = e < 0 ? r.firstChild : r.lastChild, l = o.content;
  return i < n - 1 && (l = yn(l, e, t, n, i + 1, s)), i >= t && (l = e < 0 ? o.contentMatchAt(0).fillBefore(l, r.childCount > 1 || s <= i).append(l) : l.append(o.contentMatchAt(o.childCount).fillBefore(g.empty, !0))), r.replaceChild(e < 0 ? 0 : r.childCount - 1, o.copy(l));
}
function br(r, e, t) {
  return e < r.openStart && (r = new y(yn(r.content, -1, e, r.openStart, 0, r.openEnd), e, r.openEnd)), t < r.openEnd && (r = new y(yn(r.content, 1, t, r.openEnd, 0, 0), r.openStart, t)), r;
}
const Fi = {
  thead: ["table"],
  tbody: ["table"],
  tfoot: ["table"],
  caption: ["table"],
  colgroup: ["table"],
  col: ["table", "colgroup"],
  tr: ["table", "tbody"],
  td: ["table", "tbody", "tr"],
  th: ["table", "tbody", "tr"]
};
let Mr = null;
function Vi() {
  return Mr || (Mr = document.implementation.createHTMLDocument("title"));
}
function Nl(r) {
  let e = /^(\s*<meta [^>]*>)*/.exec(r);
  e && (r = r.slice(e[0].length));
  let t = Vi().createElement("div"), n = /<([a-z][^>\s]+)/i.exec(r), i;
  if ((i = n && Fi[n[1].toLowerCase()]) && (r = i.map((s) => "<" + s + ">").join("") + r + i.map((s) => "</" + s + ">").reverse().join("")), t.innerHTML = r, i)
    for (let s = 0; s < i.length; s++)
      t = t.querySelector(i[s]) || t;
  return t;
}
function wl(r) {
  let e = r.querySelectorAll(v ? "span:not([class]):not([style])" : "span.Apple-converted-space");
  for (let t = 0; t < e.length; t++) {
    let n = e[t];
    n.childNodes.length == 1 && n.textContent == "\xA0" && n.parentNode && n.parentNode.replaceChild(r.ownerDocument.createTextNode(" "), n);
  }
}
function Tl(r, e) {
  if (!r.size)
    return r;
  let t = r.content.firstChild.type.schema, n;
  try {
    n = JSON.parse(e);
  } catch {
    return r;
  }
  let { content: i, openStart: s, openEnd: o } = r;
  for (let l = n.length - 2; l >= 0; l -= 2) {
    let a = t.nodes[n[l]];
    if (!a || a.hasRequiredAttrs())
      break;
    i = g.from(a.create(n[l + 1], i)), s++, o++;
  }
  return new y(i, s, o);
}
const P = {}, z = {}, Dl = { touchstart: !0, touchmove: !0 };
class El {
  constructor() {
    this.shiftKey = !1, this.mouseDown = null, this.lastKeyCode = null, this.lastKeyCodeTime = 0, this.lastClick = { time: 0, x: 0, y: 0, type: "" }, this.lastSelectionOrigin = null, this.lastSelectionTime = 0, this.lastIOSEnter = 0, this.lastIOSEnterFallbackTimeout = -1, this.lastFocus = 0, this.lastTouch = 0, this.lastAndroidDelete = 0, this.composing = !1, this.composingTimeout = -1, this.compositionNodes = [], this.compositionEndedAt = -2e8, this.domChangeCount = 0, this.eventHandlers = /* @__PURE__ */ Object.create(null), this.hideSelectionGuard = null;
  }
}
function Al(r) {
  for (let e in P) {
    let t = P[e];
    r.dom.addEventListener(e, r.input.eventHandlers[e] = (n) => {
      Rl(r, n) && !An(r, n) && (r.editable || !(n.type in z)) && t(r, n);
    }, Dl[e] ? { passive: !0 } : void 0);
  }
  R && r.dom.addEventListener("input", () => null), kn(r);
}
function pe(r, e) {
  r.input.lastSelectionOrigin = e, r.input.lastSelectionTime = Date.now();
}
function Il(r) {
  r.domObserver.stop();
  for (let e in r.input.eventHandlers)
    r.dom.removeEventListener(e, r.input.eventHandlers[e]);
  clearTimeout(r.input.composingTimeout), clearTimeout(r.input.lastIOSEnterFallbackTimeout);
}
function kn(r) {
  r.someProp("handleDOMEvents", (e) => {
    for (let t in e)
      r.input.eventHandlers[t] || r.dom.addEventListener(t, r.input.eventHandlers[t] = (n) => An(r, n));
  });
}
function An(r, e) {
  return r.someProp("handleDOMEvents", (t) => {
    let n = t[e.type];
    return n ? n(r, e) || e.defaultPrevented : !1;
  });
}
function Rl(r, e) {
  if (!e.bubbles)
    return !0;
  if (e.defaultPrevented)
    return !1;
  for (let t = e.target; t != r.dom; t = t.parentNode)
    if (!t || t.nodeType == 11 || t.pmViewDesc && t.pmViewDesc.stopEvent(e))
      return !1;
  return !0;
}
function Pl(r, e) {
  !An(r, e) && P[e.type] && (r.editable || !(e.type in z)) && P[e.type](r, e);
}
z.keydown = (r, e) => {
  let t = e;
  if (r.input.shiftKey = t.keyCode == 16 || t.shiftKey, !Ji(r, t) && (r.input.lastKeyCode = t.keyCode, r.input.lastKeyCodeTime = Date.now(), !(_ && v && t.keyCode == 13)))
    if (t.keyCode != 229 && r.domObserver.forceFlush(), Ue && t.keyCode == 13 && !t.ctrlKey && !t.altKey && !t.metaKey) {
      let n = Date.now();
      r.input.lastIOSEnter = n, r.input.lastIOSEnterFallbackTimeout = setTimeout(() => {
        r.input.lastIOSEnter == n && (r.someProp("handleKeyDown", (i) => i(r, Ve(13, "Enter"))), r.input.lastIOSEnter = 0);
      }, 200);
    } else
      r.someProp("handleKeyDown", (n) => n(r, t)) || Ml(r, t) ? t.preventDefault() : pe(r, "key");
};
z.keyup = (r, e) => {
  e.keyCode == 16 && (r.input.shiftKey = !1);
};
z.keypress = (r, e) => {
  let t = e;
  if (Ji(r, t) || !t.charCode || t.ctrlKey && !t.altKey || H && t.metaKey)
    return;
  if (r.someProp("handleKeyPress", (i) => i(r, t))) {
    t.preventDefault();
    return;
  }
  let n = r.state.selection;
  if (!(n instanceof O) || !n.$from.sameParent(n.$to)) {
    let i = String.fromCharCode(t.charCode);
    r.someProp("handleTextInput", (s) => s(r, n.$from.pos, n.$to.pos, i)) || r.dispatch(r.state.tr.insertText(i).scrollIntoView()), t.preventDefault();
  }
};
function Bt(r) {
  return { left: r.clientX, top: r.clientY };
}
function zl(r, e) {
  let t = e.x - r.clientX, n = e.y - r.clientY;
  return t * t + n * n < 100;
}
function In(r, e, t, n, i) {
  if (n == -1)
    return !1;
  let s = r.state.doc.resolve(n);
  for (let o = s.depth + 1; o > 0; o--)
    if (r.someProp(e, (l) => o > s.depth ? l(r, t, s.nodeAfter, s.before(o), i, !0) : l(r, t, s.node(o), s.before(o), i, !1)))
      return !0;
  return !1;
}
function qe(r, e, t) {
  r.focused || r.focus();
  let n = r.state.tr.setSelection(e);
  t == "pointer" && n.setMeta("pointer", !0), r.dispatch(n);
}
function Bl(r, e) {
  if (e == -1)
    return !1;
  let t = r.state.doc.resolve(e), n = t.nodeAfter;
  return n && n.isAtom && x.isSelectable(n) ? (qe(r, new x(t), "pointer"), !0) : !1;
}
function vl(r, e) {
  if (e == -1)
    return !1;
  let t = r.state.selection, n, i;
  t instanceof x && (n = t.node);
  let s = r.state.doc.resolve(e);
  for (let o = s.depth + 1; o > 0; o--) {
    let l = o > s.depth ? s.nodeAfter : s.node(o);
    if (x.isSelectable(l)) {
      n && t.$from.depth > 0 && o >= t.$from.depth && s.before(t.$from.depth + 1) == t.$from.pos ? i = s.before(t.$from.depth) : i = s.before(o);
      break;
    }
  }
  return i != null ? (qe(r, x.create(r.state.doc, i), "pointer"), !0) : !1;
}
function Fl(r, e, t, n, i) {
  return In(r, "handleClickOn", e, t, n) || r.someProp("handleClick", (s) => s(r, e, n)) || (i ? vl(r, t) : Bl(r, t));
}
function Vl(r, e, t, n) {
  return In(r, "handleDoubleClickOn", e, t, n) || r.someProp("handleDoubleClick", (i) => i(r, e, n));
}
function Ll(r, e, t, n) {
  return In(r, "handleTripleClickOn", e, t, n) || r.someProp("handleTripleClick", (i) => i(r, e, n)) || Jl(r, t, n);
}
function Jl(r, e, t) {
  if (t.button != 0)
    return !1;
  let n = r.state.doc;
  if (e == -1)
    return n.inlineContent ? (qe(r, O.create(n, 0, n.content.size), "pointer"), !0) : !1;
  let i = n.resolve(e);
  for (let s = i.depth + 1; s > 0; s--) {
    let o = s > i.depth ? i.nodeAfter : i.node(s), l = i.before(s);
    if (o.inlineContent)
      qe(r, O.create(n, l + 1, l + 1 + o.content.size), "pointer");
    else if (x.isSelectable(o))
      qe(r, x.create(n, l), "pointer");
    else
      continue;
    return !0;
  }
}
function Rn(r) {
  return Tt(r);
}
const Li = H ? "metaKey" : "ctrlKey";
P.mousedown = (r, e) => {
  let t = e;
  r.input.shiftKey = t.shiftKey;
  let n = Rn(r), i = Date.now(), s = "singleClick";
  i - r.input.lastClick.time < 500 && zl(t, r.input.lastClick) && !t[Li] && (r.input.lastClick.type == "singleClick" ? s = "doubleClick" : r.input.lastClick.type == "doubleClick" && (s = "tripleClick")), r.input.lastClick = { time: i, x: t.clientX, y: t.clientY, type: s };
  let o = r.posAtCoords(Bt(t));
  !o || (s == "singleClick" ? (r.input.mouseDown && r.input.mouseDown.done(), r.input.mouseDown = new Wl(r, o, t, !!n)) : (s == "doubleClick" ? Vl : Ll)(r, o.pos, o.inside, t) ? t.preventDefault() : pe(r, "pointer"));
};
class Wl {
  constructor(e, t, n, i) {
    this.view = e, this.pos = t, this.event = n, this.flushed = i, this.delayedSelectionSync = !1, this.mightDrag = null, this.startDoc = e.state.doc, this.selectNode = !!n[Li], this.allowDefault = n.shiftKey;
    let s, o;
    if (t.inside > -1)
      s = e.state.doc.nodeAt(t.inside), o = t.inside;
    else {
      let c = e.state.doc.resolve(t.pos);
      s = c.parent, o = c.depth ? c.before() : 0;
    }
    const l = i ? null : n.target, a = l ? e.docView.nearestDesc(l, !0) : null;
    this.target = a ? a.dom : null;
    let { selection: h } = e.state;
    (n.button == 0 && s.type.spec.draggable && s.type.spec.selectable !== !1 || h instanceof x && h.from <= o && h.to > o) && (this.mightDrag = {
      node: s,
      pos: o,
      addAttr: !!(this.target && !this.target.draggable),
      setUneditable: !!(this.target && X && !this.target.hasAttribute("contentEditable"))
    }), this.target && this.mightDrag && (this.mightDrag.addAttr || this.mightDrag.setUneditable) && (this.view.domObserver.stop(), this.mightDrag.addAttr && (this.target.draggable = !0), this.mightDrag.setUneditable && setTimeout(() => {
      this.view.input.mouseDown == this && this.target.setAttribute("contentEditable", "false");
    }, 20), this.view.domObserver.start()), e.root.addEventListener("mouseup", this.up = this.up.bind(this)), e.root.addEventListener("mousemove", this.move = this.move.bind(this)), pe(e, "pointer");
  }
  done() {
    this.view.root.removeEventListener("mouseup", this.up), this.view.root.removeEventListener("mousemove", this.move), this.mightDrag && this.target && (this.view.domObserver.stop(), this.mightDrag.addAttr && this.target.removeAttribute("draggable"), this.mightDrag.setUneditable && this.target.removeAttribute("contentEditable"), this.view.domObserver.start()), this.delayedSelectionSync && setTimeout(() => le(this.view)), this.view.input.mouseDown = null;
  }
  up(e) {
    if (this.done(), !this.view.dom.contains(e.target))
      return;
    let t = this.pos;
    this.view.state.doc != this.startDoc && (t = this.view.posAtCoords(Bt(e))), this.updateAllowDefault(e), this.allowDefault || !t ? pe(this.view, "pointer") : Fl(this.view, t.pos, t.inside, e, this.selectNode) ? e.preventDefault() : e.button == 0 && (this.flushed || R && this.mightDrag && !this.mightDrag.node.isAtom || v && !this.view.state.selection.visible && Math.min(Math.abs(t.pos - this.view.state.selection.from), Math.abs(t.pos - this.view.state.selection.to)) <= 2) ? (qe(this.view, C.near(this.view.state.doc.resolve(t.pos)), "pointer"), e.preventDefault()) : pe(this.view, "pointer");
  }
  move(e) {
    this.updateAllowDefault(e), pe(this.view, "pointer"), e.buttons == 0 && this.done();
  }
  updateAllowDefault(e) {
    !this.allowDefault && (Math.abs(this.event.x - e.clientX) > 4 || Math.abs(this.event.y - e.clientY) > 4) && (this.allowDefault = !0);
  }
}
P.touchstart = (r) => {
  r.input.lastTouch = Date.now(), Rn(r), pe(r, "pointer");
};
P.touchmove = (r) => {
  r.input.lastTouch = Date.now(), pe(r, "pointer");
};
P.contextmenu = (r) => Rn(r);
function Ji(r, e) {
  return r.composing ? !0 : R && Math.abs(e.timeStamp - r.input.compositionEndedAt) < 500 ? (r.input.compositionEndedAt = -2e8, !0) : !1;
}
const ql = _ ? 5e3 : -1;
z.compositionstart = z.compositionupdate = (r) => {
  if (!r.composing) {
    r.domObserver.flush();
    let { state: e } = r, t = e.selection.$from;
    if (e.selection.empty && (e.storedMarks || !t.textOffset && t.parentOffset && t.nodeBefore.marks.some((n) => n.type.spec.inclusive === !1)))
      r.markCursor = r.state.storedMarks || t.marks(), Tt(r, !0), r.markCursor = null;
    else if (Tt(r), X && e.selection.empty && t.parentOffset && !t.textOffset && t.nodeBefore.marks.length) {
      let n = r.domSelectionRange();
      for (let i = n.focusNode, s = n.focusOffset; i && i.nodeType == 1 && s != 0; ) {
        let o = s < 0 ? i.lastChild : i.childNodes[s - 1];
        if (!o)
          break;
        if (o.nodeType == 3) {
          r.domSelection().collapse(o, o.nodeValue.length);
          break;
        } else
          i = o, s = -1;
      }
    }
    r.input.composing = !0;
  }
  Wi(r, ql);
};
z.compositionend = (r, e) => {
  r.composing && (r.input.composing = !1, r.input.compositionEndedAt = e.timeStamp, Wi(r, 20));
};
function Wi(r, e) {
  clearTimeout(r.input.composingTimeout), e > -1 && (r.input.composingTimeout = setTimeout(() => Tt(r), e));
}
function qi(r) {
  for (r.composing && (r.input.composing = !1, r.input.compositionEndedAt = Kl()); r.input.compositionNodes.length > 0; )
    r.input.compositionNodes.pop().markParentsDirty();
}
function Kl() {
  let r = document.createEvent("Event");
  return r.initEvent("event", !0, !0), r.timeStamp;
}
function Tt(r, e = !1) {
  if (!(_ && r.domObserver.flushingSoon >= 0)) {
    if (r.domObserver.forceFlush(), qi(r), e || r.docView && r.docView.dirty) {
      let t = Dn(r);
      return t && !t.eq(r.state.selection) ? r.dispatch(r.state.tr.setSelection(t)) : r.updateState(r.state), !0;
    }
    return !1;
  }
}
function Hl(r, e) {
  if (!r.dom.parentNode)
    return;
  let t = r.dom.parentNode.appendChild(document.createElement("div"));
  t.appendChild(e), t.style.cssText = "position: fixed; left: -10000px; top: 10px";
  let n = getSelection(), i = document.createRange();
  i.selectNodeContents(e), r.dom.blur(), n.removeAllRanges(), n.addRange(i), setTimeout(() => {
    t.parentNode && t.parentNode.removeChild(t), r.focus();
  }, 50);
}
const je = F && ge < 15 || Ue && $o < 604;
P.copy = z.cut = (r, e) => {
  let t = e, n = r.state.selection, i = t.type == "cut";
  if (n.empty)
    return;
  let s = je ? null : t.clipboardData, o = n.content(), { dom: l, text: a } = Ri(r, o);
  s ? (t.preventDefault(), s.clearData(), s.setData("text/html", l.innerHTML), s.setData("text/plain", a)) : Hl(r, l), i && r.dispatch(r.state.tr.deleteSelection().scrollIntoView().setMeta("uiEvent", "cut"));
};
function $l(r) {
  return r.openStart == 0 && r.openEnd == 0 && r.content.childCount == 1 ? r.content.firstChild : null;
}
function Ul(r, e) {
  if (!r.dom.parentNode)
    return;
  let t = r.input.shiftKey || r.state.selection.$from.parent.type.spec.code, n = r.dom.parentNode.appendChild(document.createElement(t ? "textarea" : "div"));
  t || (n.contentEditable = "true"), n.style.cssText = "position: fixed; left: -10000px; top: 10px", n.focus(), setTimeout(() => {
    r.focus(), n.parentNode && n.parentNode.removeChild(n), t ? xn(r, n.value, null, e) : xn(r, n.textContent, n.innerHTML, e);
  }, 50);
}
function xn(r, e, t, n) {
  let i = Pi(r, e, t, r.input.shiftKey, r.state.selection.$from);
  if (r.someProp("handlePaste", (l) => l(r, n, i || y.empty)))
    return !0;
  if (!i)
    return !1;
  let s = $l(i), o = s ? r.state.tr.replaceSelectionWith(s, r.input.shiftKey) : r.state.tr.replaceSelection(i);
  return r.dispatch(o.scrollIntoView().setMeta("paste", !0).setMeta("uiEvent", "paste")), !0;
}
z.paste = (r, e) => {
  let t = e;
  if (r.composing && !_)
    return;
  let n = je ? null : t.clipboardData;
  n && xn(r, n.getData("text/plain"), n.getData("text/html"), t) ? t.preventDefault() : Ul(r, t);
};
class jl {
  constructor(e, t) {
    this.slice = e, this.move = t;
  }
}
const Ki = H ? "altKey" : "ctrlKey";
P.dragstart = (r, e) => {
  let t = e, n = r.input.mouseDown;
  if (n && n.done(), !t.dataTransfer)
    return;
  let i = r.state.selection, s = i.empty ? null : r.posAtCoords(Bt(t));
  if (!(s && s.pos >= i.from && s.pos <= (i instanceof x ? i.to - 1 : i.to))) {
    if (n && n.mightDrag)
      r.dispatch(r.state.tr.setSelection(x.create(r.state.doc, n.mightDrag.pos)));
    else if (t.target && t.target.nodeType == 1) {
      let h = r.docView.nearestDesc(t.target, !0);
      h && h.node.type.spec.draggable && h != r.docView && r.dispatch(r.state.tr.setSelection(x.create(r.state.doc, h.posBefore)));
    }
  }
  let o = r.state.selection.content(), { dom: l, text: a } = Ri(r, o);
  t.dataTransfer.clearData(), t.dataTransfer.setData(je ? "Text" : "text/html", l.innerHTML), t.dataTransfer.effectAllowed = "copyMove", je || t.dataTransfer.setData("text/plain", a), r.dragging = new jl(o, !t[Ki]);
};
P.dragend = (r) => {
  let e = r.dragging;
  window.setTimeout(() => {
    r.dragging == e && (r.dragging = null);
  }, 50);
};
z.dragover = z.dragenter = (r, e) => e.preventDefault();
z.drop = (r, e) => {
  let t = e, n = r.dragging;
  if (r.dragging = null, !t.dataTransfer)
    return;
  let i = r.posAtCoords(Bt(t));
  if (!i)
    return;
  let s = r.state.doc.resolve(i.pos), o = n && n.slice;
  o ? r.someProp("transformPasted", (p) => {
    o = p(o, r);
  }) : o = Pi(r, t.dataTransfer.getData(je ? "Text" : "text/plain"), je ? null : t.dataTransfer.getData("text/html"), !1, s);
  let l = !!(n && !t[Ki]);
  if (r.someProp("handleDrop", (p) => p(r, t, o || y.empty, l))) {
    t.preventDefault();
    return;
  }
  if (!o)
    return;
  t.preventDefault();
  let a = o ? Fs(r.state.doc, s.pos, o) : s.pos;
  a == null && (a = s.pos);
  let h = r.state.tr;
  l && h.deleteSelection();
  let c = h.mapping.map(a), f = o.openStart == 0 && o.openEnd == 0 && o.content.childCount == 1, d = h.doc;
  if (f ? h.replaceRangeWith(c, c, o.content.firstChild) : h.replaceRange(c, c, o), h.doc.eq(d))
    return;
  let u = h.doc.resolve(c);
  if (f && x.isSelectable(o.content.firstChild) && u.nodeAfter && u.nodeAfter.sameMarkup(o.content.firstChild))
    h.setSelection(new x(u));
  else {
    let p = h.mapping.map(a);
    h.mapping.maps[h.mapping.maps.length - 1].forEach((m, k, S, L) => p = L), h.setSelection(En(r, u, h.doc.resolve(p)));
  }
  r.focus(), r.dispatch(h.setMeta("uiEvent", "drop"));
};
P.focus = (r) => {
  r.input.lastFocus = Date.now(), r.focused || (r.domObserver.stop(), r.dom.classList.add("ProseMirror-focused"), r.domObserver.start(), r.focused = !0, setTimeout(() => {
    r.docView && r.hasFocus() && !r.domObserver.currentSelection.eq(r.domSelectionRange()) && le(r);
  }, 20));
};
P.blur = (r, e) => {
  let t = e;
  r.focused && (r.domObserver.stop(), r.dom.classList.remove("ProseMirror-focused"), r.domObserver.start(), t.relatedTarget && r.dom.contains(t.relatedTarget) && r.domObserver.currentSelection.clear(), r.focused = !1);
};
P.beforeinput = (r, e) => {
  if (v && _ && e.inputType == "deleteContentBackward") {
    r.domObserver.flushSoon();
    let { domChangeCount: n } = r.input;
    setTimeout(() => {
      if (r.input.domChangeCount != n || (r.dom.blur(), r.focus(), r.someProp("handleKeyDown", (s) => s(r, Ve(8, "Backspace")))))
        return;
      let { $cursor: i } = r.state.selection;
      i && i.pos > 0 && r.dispatch(r.state.tr.delete(i.pos - 1, i.pos).scrollIntoView());
    }, 50);
  }
};
for (let r in z)
  P[r] = z[r];
function ot(r, e) {
  if (r == e)
    return !0;
  for (let t in r)
    if (r[t] !== e[t])
      return !1;
  for (let t in e)
    if (!(t in r))
      return !1;
  return !0;
}
class Pn {
  constructor(e, t) {
    this.toDOM = e, this.spec = t || we, this.side = this.spec.side || 0;
  }
  map(e, t, n, i) {
    let { pos: s, deleted: o } = e.mapResult(t.from + i, this.side < 0 ? -1 : 1);
    return o ? null : new G(s - n, s - n, this);
  }
  valid() {
    return !0;
  }
  eq(e) {
    return this == e || e instanceof Pn && (this.spec.key && this.spec.key == e.spec.key || this.toDOM == e.toDOM && ot(this.spec, e.spec));
  }
  destroy(e) {
    this.spec.destroy && this.spec.destroy(e);
  }
}
class ye {
  constructor(e, t) {
    this.attrs = e, this.spec = t || we;
  }
  map(e, t, n, i) {
    let s = e.map(t.from + i, this.spec.inclusiveStart ? -1 : 1) - n, o = e.map(t.to + i, this.spec.inclusiveEnd ? 1 : -1) - n;
    return s >= o ? null : new G(s, o, this);
  }
  valid(e, t) {
    return t.from < t.to;
  }
  eq(e) {
    return this == e || e instanceof ye && ot(this.attrs, e.attrs) && ot(this.spec, e.spec);
  }
  static is(e) {
    return e.type instanceof ye;
  }
  destroy() {
  }
}
class zn {
  constructor(e, t) {
    this.attrs = e, this.spec = t || we;
  }
  map(e, t, n, i) {
    let s = e.mapResult(t.from + i, 1);
    if (s.deleted)
      return null;
    let o = e.mapResult(t.to + i, -1);
    return o.deleted || o.pos <= s.pos ? null : new G(s.pos - n, o.pos - n, this);
  }
  valid(e, t) {
    let { index: n, offset: i } = e.content.findIndex(t.from), s;
    return i == t.from && !(s = e.child(n)).isText && i + s.nodeSize == t.to;
  }
  eq(e) {
    return this == e || e instanceof zn && ot(this.attrs, e.attrs) && ot(this.spec, e.spec);
  }
  destroy() {
  }
}
class G {
  constructor(e, t, n) {
    this.from = e, this.to = t, this.type = n;
  }
  copy(e, t) {
    return new G(e, t, this.type);
  }
  eq(e, t = 0) {
    return this.type.eq(e.type) && this.from + t == e.from && this.to + t == e.to;
  }
  map(e, t, n) {
    return this.type.map(e, this, t, n);
  }
  static widget(e, t, n) {
    return new G(e, e, new Pn(t, n));
  }
  static inline(e, t, n, i) {
    return new G(e, t, new ye(n, i));
  }
  static node(e, t, n, i) {
    return new G(e, t, new zn(n, i));
  }
  get spec() {
    return this.type.spec;
  }
  get inline() {
    return this.type instanceof ye;
  }
}
const Be = [], we = {};
class w {
  constructor(e, t) {
    this.local = e.length ? e : Be, this.children = t.length ? t : Be;
  }
  static create(e, t) {
    return t.length ? Dt(t, e, 0, we) : A;
  }
  find(e, t, n) {
    let i = [];
    return this.findInner(e == null ? 0 : e, t == null ? 1e9 : t, i, 0, n), i;
  }
  findInner(e, t, n, i, s) {
    for (let o = 0; o < this.local.length; o++) {
      let l = this.local[o];
      l.from <= t && l.to >= e && (!s || s(l.spec)) && n.push(l.copy(l.from + i, l.to + i));
    }
    for (let o = 0; o < this.children.length; o += 3)
      if (this.children[o] < t && this.children[o + 1] > e) {
        let l = this.children[o] + 1;
        this.children[o + 2].findInner(e - l, t - l, n, i + l, s);
      }
  }
  map(e, t, n) {
    return this == A || e.maps.length == 0 ? this : this.mapInner(e, t, 0, 0, n || we);
  }
  mapInner(e, t, n, i, s) {
    let o;
    for (let l = 0; l < this.local.length; l++) {
      let a = this.local[l].map(e, n, i);
      a && a.type.valid(t, a) ? (o || (o = [])).push(a) : s.onRemove && s.onRemove(this.local[l].spec);
    }
    return this.children.length ? Gl(this.children, o || [], e, t, n, i, s) : o ? new w(o.sort(Te), Be) : A;
  }
  add(e, t) {
    return t.length ? this == A ? w.create(e, t) : this.addInner(e, t, 0) : this;
  }
  addInner(e, t, n) {
    let i, s = 0;
    e.forEach((l, a) => {
      let h = a + n, c;
      if (!!(c = $i(t, l, h))) {
        for (i || (i = this.children.slice()); s < i.length && i[s] < a; )
          s += 3;
        i[s] == a ? i[s + 2] = i[s + 2].addInner(l, c, h + 1) : i.splice(s, 0, a, a + l.nodeSize, Dt(c, l, h + 1, we)), s += 3;
      }
    });
    let o = Hi(s ? Ui(t) : t, -n);
    for (let l = 0; l < o.length; l++)
      o[l].type.valid(e, o[l]) || o.splice(l--, 1);
    return new w(o.length ? this.local.concat(o).sort(Te) : this.local, i || this.children);
  }
  remove(e) {
    return e.length == 0 || this == A ? this : this.removeInner(e, 0);
  }
  removeInner(e, t) {
    let n = this.children, i = this.local;
    for (let s = 0; s < n.length; s += 3) {
      let o, l = n[s] + t, a = n[s + 1] + t;
      for (let c = 0, f; c < e.length; c++)
        (f = e[c]) && f.from > l && f.to < a && (e[c] = null, (o || (o = [])).push(f));
      if (!o)
        continue;
      n == this.children && (n = this.children.slice());
      let h = n[s + 2].removeInner(o, l + 1);
      h != A ? n[s + 2] = h : (n.splice(s, 3), s -= 3);
    }
    if (i.length) {
      for (let s = 0, o; s < e.length; s++)
        if (o = e[s])
          for (let l = 0; l < i.length; l++)
            i[l].eq(o, t) && (i == this.local && (i = this.local.slice()), i.splice(l--, 1));
    }
    return n == this.children && i == this.local ? this : i.length || n.length ? new w(i, n) : A;
  }
  forChild(e, t) {
    if (this == A)
      return this;
    if (t.isLeaf)
      return w.empty;
    let n, i;
    for (let l = 0; l < this.children.length; l += 3)
      if (this.children[l] >= e) {
        this.children[l] == e && (n = this.children[l + 2]);
        break;
      }
    let s = e + 1, o = s + t.content.size;
    for (let l = 0; l < this.local.length; l++) {
      let a = this.local[l];
      if (a.from < o && a.to > s && a.type instanceof ye) {
        let h = Math.max(s, a.from) - s, c = Math.min(o, a.to) - s;
        h < c && (i || (i = [])).push(a.copy(h, c));
      }
    }
    if (i) {
      let l = new w(i.sort(Te), Be);
      return n ? new fe([l, n]) : l;
    }
    return n || A;
  }
  eq(e) {
    if (this == e)
      return !0;
    if (!(e instanceof w) || this.local.length != e.local.length || this.children.length != e.children.length)
      return !1;
    for (let t = 0; t < this.local.length; t++)
      if (!this.local[t].eq(e.local[t]))
        return !1;
    for (let t = 0; t < this.children.length; t += 3)
      if (this.children[t] != e.children[t] || this.children[t + 1] != e.children[t + 1] || !this.children[t + 2].eq(e.children[t + 2]))
        return !1;
    return !0;
  }
  locals(e) {
    return Bn(this.localsInner(e));
  }
  localsInner(e) {
    if (this == A)
      return Be;
    if (e.inlineContent || !this.local.some(ye.is))
      return this.local;
    let t = [];
    for (let n = 0; n < this.local.length; n++)
      this.local[n].type instanceof ye || t.push(this.local[n]);
    return t;
  }
}
w.empty = new w([], []);
w.removeOverlap = Bn;
const A = w.empty;
class fe {
  constructor(e) {
    this.members = e;
  }
  map(e, t) {
    const n = this.members.map((i) => i.map(e, t, we));
    return fe.from(n);
  }
  forChild(e, t) {
    if (t.isLeaf)
      return w.empty;
    let n = [];
    for (let i = 0; i < this.members.length; i++) {
      let s = this.members[i].forChild(e, t);
      s != A && (s instanceof fe ? n = n.concat(s.members) : n.push(s));
    }
    return fe.from(n);
  }
  eq(e) {
    if (!(e instanceof fe) || e.members.length != this.members.length)
      return !1;
    for (let t = 0; t < this.members.length; t++)
      if (!this.members[t].eq(e.members[t]))
        return !1;
    return !0;
  }
  locals(e) {
    let t, n = !0;
    for (let i = 0; i < this.members.length; i++) {
      let s = this.members[i].localsInner(e);
      if (!!s.length)
        if (!t)
          t = s;
        else {
          n && (t = t.slice(), n = !1);
          for (let o = 0; o < s.length; o++)
            t.push(s[o]);
        }
    }
    return t ? Bn(n ? t : t.sort(Te)) : Be;
  }
  static from(e) {
    switch (e.length) {
      case 0:
        return A;
      case 1:
        return e[0];
      default:
        return new fe(e.every((t) => t instanceof w) ? e : e.reduce((t, n) => t.concat(n instanceof w ? n : n.members), []));
    }
  }
}
function Gl(r, e, t, n, i, s, o) {
  let l = r.slice();
  for (let h = 0, c = s; h < t.maps.length; h++) {
    let f = 0;
    t.maps[h].forEach((d, u, p, m) => {
      let k = m - p - (u - d);
      for (let S = 0; S < l.length; S += 3) {
        let L = l[S + 1];
        if (L < 0 || d > L + c - f)
          continue;
        let B = l[S] + c - f;
        u >= B ? l[S + 1] = d <= B ? -2 : -1 : p >= i && k && (l[S] += k, l[S + 1] += k);
      }
      f += k;
    }), c = t.maps[h].map(c, -1);
  }
  let a = !1;
  for (let h = 0; h < l.length; h += 3)
    if (l[h + 1] < 0) {
      if (l[h + 1] == -2) {
        a = !0, l[h + 1] = -1;
        continue;
      }
      let c = t.map(r[h] + s), f = c - i;
      if (f < 0 || f >= n.content.size) {
        a = !0;
        continue;
      }
      let d = t.map(r[h + 1] + s, -1), u = d - i, { index: p, offset: m } = n.content.findIndex(f), k = n.maybeChild(p);
      if (k && m == f && m + k.nodeSize == u) {
        let S = l[h + 2].mapInner(t, k, c + 1, r[h] + s + 1, o);
        S != A ? (l[h] = f, l[h + 1] = u, l[h + 2] = S) : (l[h + 1] = -2, a = !0);
      } else
        a = !0;
    }
  if (a) {
    let h = Yl(l, r, e, t, i, s, o), c = Dt(h, n, 0, o);
    e = c.local;
    for (let f = 0; f < l.length; f += 3)
      l[f + 1] < 0 && (l.splice(f, 3), f -= 3);
    for (let f = 0, d = 0; f < c.children.length; f += 3) {
      let u = c.children[f];
      for (; d < l.length && l[d] < u; )
        d += 3;
      l.splice(d, 0, c.children[f], c.children[f + 1], c.children[f + 2]);
    }
  }
  return new w(e.sort(Te), l);
}
function Hi(r, e) {
  if (!e || !r.length)
    return r;
  let t = [];
  for (let n = 0; n < r.length; n++) {
    let i = r[n];
    t.push(new G(i.from + e, i.to + e, i.type));
  }
  return t;
}
function Yl(r, e, t, n, i, s, o) {
  function l(a, h) {
    for (let c = 0; c < a.local.length; c++) {
      let f = a.local[c].map(n, i, h);
      f ? t.push(f) : o.onRemove && o.onRemove(a.local[c].spec);
    }
    for (let c = 0; c < a.children.length; c += 3)
      l(a.children[c + 2], a.children[c] + h + 1);
  }
  for (let a = 0; a < r.length; a += 3)
    r[a + 1] == -1 && l(r[a + 2], e[a] + s + 1);
  return t;
}
function $i(r, e, t) {
  if (e.isLeaf)
    return null;
  let n = t + e.nodeSize, i = null;
  for (let s = 0, o; s < r.length; s++)
    (o = r[s]) && o.from > t && o.to < n && ((i || (i = [])).push(o), r[s] = null);
  return i;
}
function Ui(r) {
  let e = [];
  for (let t = 0; t < r.length; t++)
    r[t] != null && e.push(r[t]);
  return e;
}
function Dt(r, e, t, n) {
  let i = [], s = !1;
  e.forEach((l, a) => {
    let h = $i(r, l, a + t);
    if (h) {
      s = !0;
      let c = Dt(h, l, t + a + 1, n);
      c != A && i.push(a, a + l.nodeSize, c);
    }
  });
  let o = Hi(s ? Ui(r) : r, -t).sort(Te);
  for (let l = 0; l < o.length; l++)
    o[l].type.valid(e, o[l]) || (n.onRemove && n.onRemove(o[l].spec), o.splice(l--, 1));
  return o.length || i.length ? new w(o, i) : A;
}
function Te(r, e) {
  return r.from - e.from || r.to - e.to;
}
function Bn(r) {
  let e = r;
  for (let t = 0; t < e.length - 1; t++) {
    let n = e[t];
    if (n.from != n.to)
      for (let i = t + 1; i < e.length; i++) {
        let s = e[i];
        if (s.from == n.from) {
          s.to != n.to && (e == r && (e = r.slice()), e[i] = s.copy(s.from, n.to), Cr(e, i + 1, s.copy(n.to, s.to)));
          continue;
        } else {
          s.from < n.to && (e == r && (e = r.slice()), e[t] = n.copy(n.from, s.from), Cr(e, i, n.copy(s.from, n.to)));
          break;
        }
      }
  }
  return e;
}
function Cr(r, e, t) {
  for (; e < r.length && Te(t, r[e]) > 0; )
    e++;
  r.splice(e, 0, t);
}
function rn(r) {
  let e = [];
  return r.someProp("decorations", (t) => {
    let n = t(r.state);
    n && n != A && e.push(n);
  }), r.cursorWrapper && e.push(w.create(r.state.doc, [r.cursorWrapper.deco])), fe.from(e);
}
const Xl = {
  childList: !0,
  characterData: !0,
  characterDataOldValue: !0,
  attributes: !0,
  attributeOldValue: !0,
  subtree: !0
}, Zl = F && ge <= 11;
class Ql {
  constructor() {
    this.anchorNode = null, this.anchorOffset = 0, this.focusNode = null, this.focusOffset = 0;
  }
  set(e) {
    this.anchorNode = e.anchorNode, this.anchorOffset = e.anchorOffset, this.focusNode = e.focusNode, this.focusOffset = e.focusOffset;
  }
  clear() {
    this.anchorNode = this.focusNode = null;
  }
  eq(e) {
    return e.anchorNode == this.anchorNode && e.anchorOffset == this.anchorOffset && e.focusNode == this.focusNode && e.focusOffset == this.focusOffset;
  }
}
class _l {
  constructor(e, t) {
    this.view = e, this.handleDOMChange = t, this.queue = [], this.flushingSoon = -1, this.observer = null, this.currentSelection = new Ql(), this.onCharData = null, this.suppressingSelectionUpdates = !1, this.observer = window.MutationObserver && new window.MutationObserver((n) => {
      for (let i = 0; i < n.length; i++)
        this.queue.push(n[i]);
      F && ge <= 11 && n.some((i) => i.type == "childList" && i.removedNodes.length || i.type == "characterData" && i.oldValue.length > i.target.nodeValue.length) ? this.flushSoon() : this.flush();
    }), Zl && (this.onCharData = (n) => {
      this.queue.push({ target: n.target, type: "characterData", oldValue: n.prevValue }), this.flushSoon();
    }), this.onSelectionChange = this.onSelectionChange.bind(this);
  }
  flushSoon() {
    this.flushingSoon < 0 && (this.flushingSoon = window.setTimeout(() => {
      this.flushingSoon = -1, this.flush();
    }, 20));
  }
  forceFlush() {
    this.flushingSoon > -1 && (window.clearTimeout(this.flushingSoon), this.flushingSoon = -1, this.flush());
  }
  start() {
    this.observer && (this.observer.takeRecords(), this.observer.observe(this.view.dom, Xl)), this.onCharData && this.view.dom.addEventListener("DOMCharacterDataModified", this.onCharData), this.connectSelection();
  }
  stop() {
    if (this.observer) {
      let e = this.observer.takeRecords();
      if (e.length) {
        for (let t = 0; t < e.length; t++)
          this.queue.push(e[t]);
        window.setTimeout(() => this.flush(), 20);
      }
      this.observer.disconnect();
    }
    this.onCharData && this.view.dom.removeEventListener("DOMCharacterDataModified", this.onCharData), this.disconnectSelection();
  }
  connectSelection() {
    this.view.dom.ownerDocument.addEventListener("selectionchange", this.onSelectionChange);
  }
  disconnectSelection() {
    this.view.dom.ownerDocument.removeEventListener("selectionchange", this.onSelectionChange);
  }
  suppressSelectionUpdates() {
    this.suppressingSelectionUpdates = !0, setTimeout(() => this.suppressingSelectionUpdates = !1, 50);
  }
  onSelectionChange() {
    if (!!gr(this.view)) {
      if (this.suppressingSelectionUpdates)
        return le(this.view);
      if (F && ge <= 11 && !this.view.state.selection.empty) {
        let e = this.view.domSelectionRange();
        if (e.focusNode && Ee(e.focusNode, e.focusOffset, e.anchorNode, e.anchorOffset))
          return this.flushSoon();
      }
      this.flush();
    }
  }
  setCurSelection() {
    this.currentSelection.set(this.view.domSelectionRange());
  }
  ignoreSelectionChange(e) {
    if (!e.focusNode)
      return !0;
    let t = /* @__PURE__ */ new Set(), n;
    for (let s = e.focusNode; s; s = st(s))
      t.add(s);
    for (let s = e.anchorNode; s; s = st(s))
      if (t.has(s)) {
        n = s;
        break;
      }
    let i = n && this.view.docView.nearestDesc(n);
    if (i && i.ignoreMutation({
      type: "selection",
      target: n.nodeType == 3 ? n.parentNode : n
    }))
      return this.setCurSelection(), !0;
  }
  flush() {
    let { view: e } = this;
    if (!e.docView || this.flushingSoon > -1)
      return;
    let t = this.observer ? this.observer.takeRecords() : [];
    this.queue.length && (t = this.queue.concat(t), this.queue.length = 0);
    let n = e.domSelectionRange(), i = !this.suppressingSelectionUpdates && !this.currentSelection.eq(n) && gr(e) && !this.ignoreSelectionChange(n), s = -1, o = -1, l = !1, a = [];
    if (e.editable)
      for (let c = 0; c < t.length; c++) {
        let f = this.registerMutation(t[c], a);
        f && (s = s < 0 ? f.from : Math.min(f.from, s), o = o < 0 ? f.to : Math.max(f.to, o), f.typeOver && (l = !0));
      }
    if (X && a.length > 1) {
      let c = a.filter((f) => f.nodeName == "BR");
      if (c.length == 2) {
        let f = c[0], d = c[1];
        f.parentNode && f.parentNode.parentNode == d.parentNode ? d.remove() : f.remove();
      }
    }
    let h = null;
    s < 0 && i && e.input.lastFocus > Date.now() - 200 && e.input.lastTouch < Date.now() - 300 && Rt(n) && (h = Dn(e)) && h.eq(C.near(e.state.doc.resolve(0), 1)) ? (e.input.lastFocus = 0, le(e), this.currentSelection.set(n), e.scrollToSelection()) : (s > -1 || i) && (s > -1 && (e.docView.markDirty(s, o), ea(e)), this.handleDOMChange(s, o, l, a), e.docView && e.docView.dirty ? e.updateState(e.state) : this.currentSelection.eq(n) || le(e), this.currentSelection.set(n));
  }
  registerMutation(e, t) {
    if (t.indexOf(e.target) > -1)
      return null;
    let n = this.view.docView.nearestDesc(e.target);
    if (e.type == "attributes" && (n == this.view.docView || e.attributeName == "contenteditable" || e.attributeName == "style" && !e.oldValue && !e.target.getAttribute("style")) || !n || n.ignoreMutation(e))
      return null;
    if (e.type == "childList") {
      for (let c = 0; c < e.addedNodes.length; c++)
        t.push(e.addedNodes[c]);
      if (n.contentDOM && n.contentDOM != n.dom && !n.contentDOM.contains(e.target))
        return { from: n.posBefore, to: n.posAfter };
      let i = e.previousSibling, s = e.nextSibling;
      if (F && ge <= 11 && e.addedNodes.length)
        for (let c = 0; c < e.addedNodes.length; c++) {
          let { previousSibling: f, nextSibling: d } = e.addedNodes[c];
          (!f || Array.prototype.indexOf.call(e.addedNodes, f) < 0) && (i = f), (!d || Array.prototype.indexOf.call(e.addedNodes, d) < 0) && (s = d);
        }
      let o = i && i.parentNode == e.target ? J(i) + 1 : 0, l = n.localPosFromDOM(e.target, o, -1), a = s && s.parentNode == e.target ? J(s) : e.target.childNodes.length, h = n.localPosFromDOM(e.target, a, 1);
      return { from: l, to: h };
    } else
      return e.type == "attributes" ? { from: n.posAtStart - n.border, to: n.posAtEnd + n.border } : {
        from: n.posAtStart,
        to: n.posAtEnd,
        typeOver: e.target.nodeValue == e.oldValue
      };
  }
}
let Or = /* @__PURE__ */ new WeakMap(), Nr = !1;
function ea(r) {
  if (!Or.has(r) && (Or.set(r, null), ["normal", "nowrap", "pre-line"].indexOf(getComputedStyle(r.dom).whiteSpace) !== -1)) {
    if (r.requiresGeckoHackNode = X, Nr)
      return;
    console.warn("ProseMirror expects the CSS white-space property to be set, preferably to 'pre-wrap'. It is recommended to load style/prosemirror.css from the prosemirror-view package."), Nr = !0;
  }
}
function ta(r) {
  let e;
  function t(a) {
    a.preventDefault(), a.stopImmediatePropagation(), e = a.getTargetRanges()[0];
  }
  r.dom.addEventListener("beforeinput", t, !0), document.execCommand("indent"), r.dom.removeEventListener("beforeinput", t, !0);
  let n = e.startContainer, i = e.startOffset, s = e.endContainer, o = e.endOffset, l = r.domAtPos(r.state.selection.anchor);
  return Ee(l.node, l.offset, s, o) && ([n, i, s, o] = [s, o, n, i]), { anchorNode: n, anchorOffset: i, focusNode: s, focusOffset: o };
}
function na(r, e, t) {
  let { node: n, fromOffset: i, toOffset: s, from: o, to: l } = r.docView.parseRange(e, t), a = r.domSelectionRange(), h, c = a.anchorNode;
  if (c && r.dom.contains(c.nodeType == 1 ? c : c.parentNode) && (h = [{ node: c, offset: a.anchorOffset }], Rt(a) || h.push({ node: a.focusNode, offset: a.focusOffset })), v && r.input.lastKeyCode === 8)
    for (let k = s; k > i; k--) {
      let S = n.childNodes[k - 1], L = S.pmViewDesc;
      if (S.nodeName == "BR" && !L) {
        s = k;
        break;
      }
      if (!L || L.size)
        break;
    }
  let f = r.state.doc, d = r.someProp("domParser") || it.fromSchema(r.state.schema), u = f.resolve(o), p = null, m = d.parse(n, {
    topNode: u.parent,
    topMatch: u.parent.contentMatchAt(u.index()),
    topOpen: !0,
    from: i,
    to: s,
    preserveWhitespace: u.parent.type.whitespace == "pre" ? "full" : !0,
    findPositions: h,
    ruleFromNode: ra,
    context: u
  });
  if (h && h[0].pos != null) {
    let k = h[0].pos, S = h[1] && h[1].pos;
    S == null && (S = k), p = { anchor: k + o, head: S + o };
  }
  return { doc: m, sel: p, from: o, to: l };
}
function ra(r) {
  let e = r.pmViewDesc;
  if (e)
    return e.parseRule();
  if (r.nodeName == "BR" && r.parentNode) {
    if (R && /^(ul|ol)$/i.test(r.parentNode.nodeName)) {
      let t = document.createElement("div");
      return t.appendChild(document.createElement("li")), { skip: t };
    } else if (r.parentNode.lastChild == r || R && /^(tr|table)$/i.test(r.parentNode.nodeName))
      return { ignore: !0 };
  } else if (r.nodeName == "IMG" && r.getAttribute("mark-placeholder"))
    return { ignore: !0 };
  return null;
}
function ia(r, e, t, n, i) {
  if (e < 0) {
    let b = r.input.lastSelectionTime > Date.now() - 50 ? r.input.lastSelectionOrigin : null, Ge = Dn(r, b);
    if (Ge && !r.state.selection.eq(Ge)) {
      let Ft = r.state.tr.setSelection(Ge);
      b == "pointer" ? Ft.setMeta("pointer", !0) : b == "key" && Ft.scrollIntoView(), r.dispatch(Ft);
    }
    return;
  }
  let s = r.state.doc.resolve(e), o = s.sharedDepth(t);
  e = s.before(o + 1), t = r.state.doc.resolve(t).after(o + 1);
  let l = r.state.selection, a = na(r, e, t), h = r.state.doc, c = h.slice(a.from, a.to), f, d;
  r.input.lastKeyCode === 8 && Date.now() - 100 < r.input.lastKeyCodeTime ? (f = r.state.selection.to, d = "end") : (f = r.state.selection.from, d = "start"), r.input.lastKeyCode = null;
  let u = la(c.content, a.doc.content, a.from, f, d);
  if ((Ue && r.input.lastIOSEnter > Date.now() - 225 || _) && i.some((b) => b.nodeName == "DIV" || b.nodeName == "P" || b.nodeName == "LI") && (!u || u.endA >= u.endB) && r.someProp("handleKeyDown", (b) => b(r, Ve(13, "Enter")))) {
    r.input.lastIOSEnter = 0;
    return;
  }
  if (!u)
    if (n && l instanceof O && !l.empty && l.$head.sameParent(l.$anchor) && !r.composing && !(a.sel && a.sel.anchor != a.sel.head))
      u = { start: l.from, endA: l.to, endB: l.to };
    else {
      if (a.sel) {
        let b = wr(r, r.state.doc, a.sel);
        b && !b.eq(r.state.selection) && r.dispatch(r.state.tr.setSelection(b));
      }
      return;
    }
  if (v && r.cursorWrapper && a.sel && a.sel.anchor == r.cursorWrapper.deco.from && a.sel.head == a.sel.anchor) {
    let b = u.endB - u.start;
    a.sel = { anchor: a.sel.anchor + b, head: a.sel.anchor + b };
  }
  r.input.domChangeCount++, r.state.selection.from < r.state.selection.to && u.start == u.endB && r.state.selection instanceof O && (u.start > r.state.selection.from && u.start <= r.state.selection.from + 2 && r.state.selection.from >= a.from ? u.start = r.state.selection.from : u.endA < r.state.selection.to && u.endA >= r.state.selection.to - 2 && r.state.selection.to <= a.to && (u.endB += r.state.selection.to - u.endA, u.endA = r.state.selection.to)), F && ge <= 11 && u.endB == u.start + 1 && u.endA == u.start && u.start > a.from && a.doc.textBetween(u.start - a.from - 1, u.start - a.from + 1) == " \xA0" && (u.start--, u.endA--, u.endB--);
  let p = a.doc.resolveNoCache(u.start - a.from), m = a.doc.resolveNoCache(u.endB - a.from), k = h.resolve(u.start), S = p.sameParent(m) && p.parent.inlineContent && k.end() >= u.endA, L;
  if ((Ue && r.input.lastIOSEnter > Date.now() - 225 && (!S || i.some((b) => b.nodeName == "DIV" || b.nodeName == "P")) || !S && p.pos < a.doc.content.size && (L = C.findFrom(a.doc.resolve(p.pos + 1), 1, !0)) && L.head == m.pos) && r.someProp("handleKeyDown", (b) => b(r, Ve(13, "Enter")))) {
    r.input.lastIOSEnter = 0;
    return;
  }
  if (r.state.selection.anchor > u.start && oa(h, u.start, u.endA, p, m) && r.someProp("handleKeyDown", (b) => b(r, Ve(8, "Backspace")))) {
    _ && v && r.domObserver.suppressSelectionUpdates();
    return;
  }
  v && _ && u.endB == u.start && (r.input.lastAndroidDelete = Date.now()), _ && !S && p.start() != m.start() && m.parentOffset == 0 && p.depth == m.depth && a.sel && a.sel.anchor == a.sel.head && a.sel.head == u.endA && (u.endB -= 2, m = a.doc.resolveNoCache(u.endB - a.from), setTimeout(() => {
    r.someProp("handleKeyDown", function(b) {
      return b(r, Ve(13, "Enter"));
    });
  }, 20));
  let B = u.start, be = u.endA, K, vt, at;
  if (S) {
    if (p.pos == m.pos)
      F && ge <= 11 && p.parentOffset == 0 && (r.domObserver.suppressSelectionUpdates(), setTimeout(() => le(r), 20)), K = r.state.tr.delete(B, be), vt = h.resolve(u.start).marksAcross(h.resolve(u.endA));
    else if (u.endA == u.endB && (at = sa(p.parent.content.cut(p.parentOffset, m.parentOffset), k.parent.content.cut(k.parentOffset, u.endA - k.start()))))
      K = r.state.tr, at.type == "add" ? K.addMark(B, be, at.mark) : K.removeMark(B, be, at.mark);
    else if (p.parent.child(p.index()).isText && p.index() == m.index() - (m.textOffset ? 0 : 1)) {
      let b = p.parent.textBetween(p.parentOffset, m.parentOffset);
      if (r.someProp("handleTextInput", (Ge) => Ge(r, B, be, b)))
        return;
      K = r.state.tr.insertText(b, B, be);
    }
  }
  if (K || (K = r.state.tr.replace(B, be, a.doc.slice(u.start - a.from, u.endB - a.from))), a.sel) {
    let b = wr(r, K.doc, a.sel);
    b && !(v && _ && r.composing && b.empty && (u.start != u.endB || r.input.lastAndroidDelete < Date.now() - 100) && (b.head == B || b.head == K.mapping.map(be) - 1) || F && b.empty && b.head == B) && K.setSelection(b);
  }
  vt && K.ensureMarks(vt), r.dispatch(K.scrollIntoView());
}
function wr(r, e, t) {
  return Math.max(t.anchor, t.head) > e.content.size ? null : En(r, e.resolve(t.anchor), e.resolve(t.head));
}
function sa(r, e) {
  let t = r.firstChild.marks, n = e.firstChild.marks, i = t, s = n, o, l, a;
  for (let c = 0; c < n.length; c++)
    i = n[c].removeFromSet(i);
  for (let c = 0; c < t.length; c++)
    s = t[c].removeFromSet(s);
  if (i.length == 1 && s.length == 0)
    l = i[0], o = "add", a = (c) => c.mark(l.addToSet(c.marks));
  else if (i.length == 0 && s.length == 1)
    l = s[0], o = "remove", a = (c) => c.mark(l.removeFromSet(c.marks));
  else
    return null;
  let h = [];
  for (let c = 0; c < e.childCount; c++)
    h.push(a(e.child(c)));
  if (g.from(h).eq(r))
    return { mark: l, type: o };
}
function oa(r, e, t, n, i) {
  if (!n.parent.isTextblock || t - e <= i.pos - n.pos || sn(n, !0, !1) < i.pos)
    return !1;
  let s = r.resolve(e);
  if (s.parentOffset < s.parent.content.size || !s.parent.isTextblock)
    return !1;
  let o = r.resolve(sn(s, !0, !0));
  return !o.parent.isTextblock || o.pos > t || sn(o, !0, !1) < t ? !1 : n.parent.content.cut(n.parentOffset).eq(o.parent.content);
}
function sn(r, e, t) {
  let n = r.depth, i = e ? r.end() : r.pos;
  for (; n > 0 && (e || r.indexAfter(n) == r.node(n).childCount); )
    n--, i++, e = !1;
  if (t) {
    let s = r.node(n).maybeChild(r.indexAfter(n));
    for (; s && !s.isLeaf; )
      s = s.firstChild, i++;
  }
  return i;
}
function la(r, e, t, n, i) {
  let s = r.findDiffStart(e, t);
  if (s == null)
    return null;
  let { a: o, b: l } = r.findDiffEnd(e, t + r.size, t + e.size);
  if (i == "end") {
    let a = Math.max(0, s - Math.min(o, l));
    n -= o + a - s;
  }
  if (o < s && r.size < e.size) {
    let a = n <= s && n >= o ? s - n : 0;
    s -= a, l = s + (l - o), o = s;
  } else if (l < s) {
    let a = n <= s && n >= l ? s - n : 0;
    s -= a, o = s + (o - l), l = s;
  }
  return { start: s, endA: o, endB: l };
}
class aa {
  constructor(e, t) {
    this._root = null, this.focused = !1, this.trackWrites = null, this.mounted = !1, this.markCursor = null, this.cursorWrapper = null, this.lastSelectedViewDesc = void 0, this.input = new El(), this.prevDirectPlugins = [], this.pluginViews = [], this.requiresGeckoHackNode = !1, this.dragging = null, this._props = t, this.state = t.state, this.directPlugins = t.plugins || [], this.directPlugins.forEach(Ir), this.dispatch = this.dispatch.bind(this), this.dom = e && e.mount || document.createElement("div"), e && (e.appendChild ? e.appendChild(this.dom) : typeof e == "function" ? e(this.dom) : e.mount && (this.mounted = !0)), this.editable = Er(this), Dr(this), this.nodeViews = Ar(this), this.docView = cr(this.state.doc, Tr(this), rn(this), this.dom, this), this.domObserver = new _l(this, (n, i, s, o) => ia(this, n, i, s, o)), this.domObserver.start(), Al(this), this.updatePluginViews();
  }
  get composing() {
    return this.input.composing;
  }
  get props() {
    if (this._props.state != this.state) {
      let e = this._props;
      this._props = {};
      for (let t in e)
        this._props[t] = e[t];
      this._props.state = this.state;
    }
    return this._props;
  }
  update(e) {
    e.handleDOMEvents != this._props.handleDOMEvents && kn(this);
    let t = this._props;
    this._props = e, e.plugins && (e.plugins.forEach(Ir), this.directPlugins = e.plugins), this.updateStateInner(e.state, t);
  }
  setProps(e) {
    let t = {};
    for (let n in this._props)
      t[n] = this._props[n];
    t.state = this.state;
    for (let n in e)
      t[n] = e[n];
    this.update(t);
  }
  updateState(e) {
    this.updateStateInner(e, this._props);
  }
  updateStateInner(e, t) {
    let n = this.state, i = !1, s = !1;
    e.storedMarks && this.composing && (qi(this), s = !0), this.state = e;
    let o = n.plugins != e.plugins || this._props.plugins != t.plugins;
    if (o || this._props.plugins != t.plugins || this._props.nodeViews != t.nodeViews) {
      let d = Ar(this);
      ca(d, this.nodeViews) && (this.nodeViews = d, i = !0);
    }
    (o || t.handleDOMEvents != this._props.handleDOMEvents) && kn(this), this.editable = Er(this), Dr(this);
    let l = rn(this), a = Tr(this), h = n.plugins != e.plugins && !n.doc.eq(e.doc) ? "reset" : e.scrollToSelection > n.scrollToSelection ? "to selection" : "preserve", c = i || !this.docView.matchesNode(e.doc, a, l);
    (c || !e.selection.eq(n.selection)) && (s = !0);
    let f = h == "preserve" && s && this.dom.style.overflowAnchor == null && Go(this);
    if (s) {
      this.domObserver.stop();
      let d = c && (F || v) && !this.composing && !n.selection.empty && !e.selection.empty && ha(n.selection, e.selection);
      if (c) {
        let u = v ? this.trackWrites = this.domSelectionRange().focusNode : null;
        (i || !this.docView.update(e.doc, a, l, this)) && (this.docView.updateOuterDeco([]), this.docView.destroy(), this.docView = cr(e.doc, a, l, this.dom, this)), u && !this.trackWrites && (d = !0);
      }
      d || !(this.input.mouseDown && this.domObserver.currentSelection.eq(this.domSelectionRange()) && xl(this)) ? le(this, d) : (Ei(this, e.selection), this.domObserver.setCurSelection()), this.domObserver.start();
    }
    this.updatePluginViews(n), h == "reset" ? this.dom.scrollTop = 0 : h == "to selection" ? this.scrollToSelection() : f && Yo(f);
  }
  scrollToSelection() {
    let e = this.domSelectionRange().focusNode;
    if (!this.someProp("handleScrollToSelection", (t) => t(this)))
      if (this.state.selection instanceof x) {
        let t = this.docView.domAfterPos(this.state.selection.from);
        t.nodeType == 1 && sr(this, t.getBoundingClientRect(), e);
      } else
        sr(this, this.coordsAtPos(this.state.selection.head, 1), e);
  }
  destroyPluginViews() {
    let e;
    for (; e = this.pluginViews.pop(); )
      e.destroy && e.destroy();
  }
  updatePluginViews(e) {
    if (!e || e.plugins != this.state.plugins || this.directPlugins != this.prevDirectPlugins) {
      this.prevDirectPlugins = this.directPlugins, this.destroyPluginViews();
      for (let t = 0; t < this.directPlugins.length; t++) {
        let n = this.directPlugins[t];
        n.spec.view && this.pluginViews.push(n.spec.view(this));
      }
      for (let t = 0; t < this.state.plugins.length; t++) {
        let n = this.state.plugins[t];
        n.spec.view && this.pluginViews.push(n.spec.view(this));
      }
    } else
      for (let t = 0; t < this.pluginViews.length; t++) {
        let n = this.pluginViews[t];
        n.update && n.update(this, e);
      }
  }
  someProp(e, t) {
    let n = this._props && this._props[e], i;
    if (n != null && (i = t ? t(n) : n))
      return i;
    for (let o = 0; o < this.directPlugins.length; o++) {
      let l = this.directPlugins[o].props[e];
      if (l != null && (i = t ? t(l) : l))
        return i;
    }
    let s = this.state.plugins;
    if (s)
      for (let o = 0; o < s.length; o++) {
        let l = s[o].props[e];
        if (l != null && (i = t ? t(l) : l))
          return i;
      }
  }
  hasFocus() {
    if (F) {
      let e = this.root.activeElement;
      if (e == this.dom)
        return !0;
      if (!e || !this.dom.contains(e))
        return !1;
      for (; e && this.dom != e && this.dom.contains(e); ) {
        if (e.contentEditable == "false")
          return !1;
        e = e.parentElement;
      }
      return !0;
    }
    return this.root.activeElement == this.dom;
  }
  focus() {
    this.domObserver.stop(), this.editable && Xo(this.dom), le(this), this.domObserver.start();
  }
  get root() {
    let e = this._root;
    if (e == null) {
      for (let t = this.dom.parentNode; t; t = t.parentNode)
        if (t.nodeType == 9 || t.nodeType == 11 && t.host)
          return t.getSelection || (Object.getPrototypeOf(t).getSelection = () => t.ownerDocument.getSelection()), this._root = t;
    }
    return e || document;
  }
  posAtCoords(e) {
    return tl(this, e);
  }
  coordsAtPos(e, t = 1) {
    return bi(this, e, t);
  }
  domAtPos(e, t = 0) {
    return this.docView.domFromPos(e, t);
  }
  nodeDOM(e) {
    let t = this.docView.descAt(e);
    return t ? t.nodeDOM : null;
  }
  posAtDOM(e, t, n = -1) {
    let i = this.docView.posFromDOM(e, t, n);
    if (i == null)
      throw new RangeError("DOM position not inside the editor");
    return i;
  }
  endOfTextblock(e, t) {
    return ol(this, t || this.state, e);
  }
  destroy() {
    !this.docView || (Il(this), this.destroyPluginViews(), this.mounted ? (this.docView.update(this.state.doc, [], rn(this), this), this.dom.textContent = "") : this.dom.parentNode && this.dom.parentNode.removeChild(this.dom), this.docView.destroy(), this.docView = null);
  }
  get isDestroyed() {
    return this.docView == null;
  }
  dispatchEvent(e) {
    return Pl(this, e);
  }
  dispatch(e) {
    let t = this._props.dispatchTransaction;
    t ? t.call(this, e) : this.updateState(this.state.apply(e));
  }
  domSelectionRange() {
    return R && this.root.nodeType === 11 && Ko(this.dom.ownerDocument) == this.dom ? ta(this) : this.domSelection();
  }
  domSelection() {
    return this.root.getSelection();
  }
}
function Tr(r) {
  let e = /* @__PURE__ */ Object.create(null);
  return e.class = "ProseMirror", e.contenteditable = String(r.editable), e.translate = "no", r.someProp("attributes", (t) => {
    if (typeof t == "function" && (t = t(r.state)), t)
      for (let n in t)
        n == "class" && (e.class += " " + t[n]), n == "style" ? e.style = (e.style ? e.style + ";" : "") + t[n] : !e[n] && n != "contenteditable" && n != "nodeName" && (e[n] = String(t[n]));
  }), [G.node(0, r.state.doc.content.size, e)];
}
function Dr(r) {
  if (r.markCursor) {
    let e = document.createElement("img");
    e.className = "ProseMirror-separator", e.setAttribute("mark-placeholder", "true"), e.setAttribute("alt", ""), r.cursorWrapper = { dom: e, deco: G.widget(r.state.selection.head, e, { raw: !0, marks: r.markCursor }) };
  } else
    r.cursorWrapper = null;
}
function Er(r) {
  return !r.someProp("editable", (e) => e(r.state) === !1);
}
function ha(r, e) {
  let t = Math.min(r.$anchor.sharedDepth(r.head), e.$anchor.sharedDepth(e.head));
  return r.$anchor.start(t) != e.$anchor.start(t);
}
function Ar(r) {
  let e = /* @__PURE__ */ Object.create(null);
  function t(n) {
    for (let i in n)
      Object.prototype.hasOwnProperty.call(e, i) || (e[i] = n[i]);
  }
  return r.someProp("nodeViews", t), r.someProp("markViews", t), e;
}
function ca(r, e) {
  let t = 0, n = 0;
  for (let i in r) {
    if (r[i] != e[i])
      return !0;
    t++;
  }
  for (let i in e)
    n++;
  return t != n;
}
function Ir(r) {
  if (r.spec.state || r.spec.filterTransaction || r.spec.appendTransaction)
    throw new RangeError("Plugins passed directly to the view must not have a state component");
}
const fa = (r, e) => {
  let t = e;
  for (let n = 0; n < r.length; n++)
    switch (r[n].type) {
      case "strong":
        t = `*${t}*`;
        break;
      case "em":
        t = `_${t}_`;
        break;
      case "s":
        t = `~${t}~`;
        break;
      case "code":
        t = "```" + t + "```";
        break;
    }
  return t;
};
class ua extends aa {
  constructor(t, ...n) {
    var e = (...args) => {
      super(...args);
      re(this, "isCustomEditor");
    };
    if (n[1])
      e(...n), this.isCustomEditor = !0;
    else {
      const i = t != null ? t : {
        position: "BOTTOM",
        distance: 10
      }, s = ve.create({
        schema: j,
        plugins: [Fo(i), ...Vo]
      });
      e(n[0], { state: s }), this.isCustomEditor = !1;
    }
  }
  getWhatsappMarkdown() {
    if (this.isCustomEditor) {
      const s = new Error("Method is forbidden for custom editor");
      throw s.name = "FORBIDDEN", s;
    }
    const t = this.state.toJSON().doc.content;
    if (!t.length)
      return null;
    const n = [];
    for (let s = 0; s < t.length; s++) {
      let o = "";
      t[s].content.forEach((a) => {
        a.marks ? o += fa(a.marks, a.text) : o += a.text;
      }), n.push(o);
    }
    return n.join(`
`);
  }
}
window.WhatsAppEditor = ua;
