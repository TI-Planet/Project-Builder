/*
 * Part of TI-Planet's Project Builder
 * (C) Adrien "Adriweb" Bertrand
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 */

/* Code from Firepad, modified by Adrien "Adriweb" Bertrand */

var FirepadUserList = (function() {
  function FirepadUserList(ref, place, userId, userName, userAvatar) {
    if (!(this instanceof FirepadUserList)) { return new FirepadUserList(ref, place, userId, userName, userAvatar); }

    this.ref_ = ref;
    this.userId_ = userId;
    this.place_ = place;
    this.firebaseCallbacks_ = [];

    this.displayName_ = userName || 'Guest';
    this.displayAvatar_ = userAvatar || 'https://tiplanet.org/images/pp-blank-thumb.png';

    var self = this;
    this.firebaseOn_(ref.root().child('.info/connected'), 'value', function(s) {
      if (s.val() === true) {
        var nameRef = ref.child(self.userId_).child('name');
        nameRef.onDisconnect().remove();
        nameRef.set(self.displayName_);
        var avatarRef = ref.child(self.userId_).child('avatar');
        avatarRef.onDisconnect().remove();
        avatarRef.set(self.displayAvatar_);
      }
    });

    this.userList_ = this.makeUserList_();
    place.appendChild(this.userList_);
  }

  // This is the primary "constructor" for symmetry with Firepad.
  FirepadUserList.fromDiv = FirepadUserList;

  FirepadUserList.prototype.dispose = function() {
    this.removeFirebaseCallbacks_();
    this.ref_.child(this.userId_).child('name').remove();

    this.place_.removeChild(this.userList_);
  };

  FirepadUserList.prototype.makeUserList_ = function() {
    return elt('div', [
      this.makeHeading_(),
      elt('div', [
        this.makeUserEntryForSelf_(),
        this.makeUserEntriesForOthers_()
      ], {'class': 'firepad-userlist-users' })
    ], {'class': 'firepad-userlist' });
  };

  FirepadUserList.prototype.makeHeading_ = function() {
    var counterSpan = elt('span', '0');
    this.firebaseOn_(this.ref_, 'value', function(usersSnapshot) {
      setTextContent(counterSpan, "" + usersSnapshot.numChildren());
    });

    return elt('div', [
      elt('span', 'ONLINE ('),
      counterSpan,
      elt('span', ')')
    ], { 'class': 'firepad-userlist-heading' });
  };

  FirepadUserList.prototype.makeUserEntryForSelf_ = function() {
    var myUserRef = this.ref_.child(this.userId_);

    var avatarDiv = elt('div', null, { 'class': 'firepad-userlist-color-indicator' });
    avatarDiv.style.backgroundImage = 'url("' + this.displayAvatar_ + '")';
    this.firebaseOn_(myUserRef.child('color'), 'value', function(colorSnapshot) {
      var color = colorSnapshot.val();
      if (typeof color === 'string' && color.match(/^#[a-fA-F0-9]{3,6}$/)) {
        avatarDiv.style.boxShadow = '0 0 2px 2px ' + color;
      }
    });

    myUserRef.child('name').set(this.displayName_);
    myUserRef.child('avatar').set(this.displayAvatar_);

    var nameDiv = elt('div', this.displayName_, { 'class': 'firepad-userlist-name' });

    var finalDiv = elt('div', [ avatarDiv, nameDiv ], { 'class': 'firepad-userlist-user', 'data-uid':this.userId_ });
    finalDiv.style.display = 'none';

    return finalDiv;
  };

  FirepadUserList.prototype.makeUserEntriesForOthers_ = function() {
    var self = this;
    var userList = elt('div');
    var userId2Element = { };

    function updateChild(userSnapshot, prevChildName) {
      var userId = userSnapshot.key();
      var lastCursorPos = userSnapshot.child('cursor/position').val();
      var div = userId2Element[userId];
      if (div) {
        if (!lastCursorPos) lastCursorPos = div.getAttribute('data-lastCursorPos') || -1;
        userList.removeChild(div);
        delete userId2Element[userId];
      }
      var name = userSnapshot.child('name').val();
      if (typeof name !== 'string') { name = 'Guest'; }
      name = name.substring(0, 25);

      var color = userSnapshot.child('color').val();
      if (typeof color !== 'string' || !color.match(/^#[a-fA-F0-9]{3,6}$/)) {
        color = "#ffb"
      }

      var avatar = userSnapshot.child('avatar').val() || 'https://tiplanet.org/images/pp-blank-thumb.png';
      var avatarDiv = elt('div', null, { 'class': 'firepad-userlist-color-indicator' });
      avatarDiv.style.boxShadow = '0 0 2px 2px ' + color;
      avatarDiv.style.backgroundImage = 'url("' + avatar + '")';

      avatarDiv.addEventListener("mousedown", function(el) {
        var target_div = el.target.parentElement;
        var target_uid = target_div.getAttribute("data-uid");
        if (target_uid <= 0)
          return;
        var idx = target_div.getAttribute("data-lastCursorPos");
        var line = editor.posFromIndex(idx).line;
        if (line > -1)
        {
            editor.setCursor(line, 0);
            var cursor_line_div = document.querySelector("div.CodeMirror-activeline");
            if (cursor_line_div)
            {
                cursor_line_div.scrollIntoView();
            }
        }
      });

      var nameDiv = elt('div', name || 'Guest', { 'class': 'firepad-userlist-name' });

      var userDiv = elt('div', [ avatarDiv, nameDiv ], { 'class': 'firepad-userlist-user', 'data-uid':userId, 'data-lastCursorPos':lastCursorPos });
      userId2Element[userId] = userDiv;

      var nextElement =  prevChildName ? userId2Element[prevChildName].nextSibling : userList.firstChild;
      userList.insertBefore(userDiv, nextElement);
    }

    this.firebaseOn_(this.ref_, 'child_added', updateChild);
    this.firebaseOn_(this.ref_, 'child_changed', updateChild);
    this.firebaseOn_(this.ref_, 'child_moved', updateChild);
    this.firebaseOn_(this.ref_, 'child_removed', function(removedSnapshot) {
      var userId = removedSnapshot.key();
      var div = userId2Element[userId];
      if (div) {
        userList.removeChild(div);
        delete userId2Element[userId];
      }
    });

    return userList;
  };

  FirepadUserList.prototype.firebaseOn_ = function(ref, eventType, callback, context) {
    this.firebaseCallbacks_.push({ref: ref, eventType: eventType, callback: callback, context: context });
    ref.on(eventType, callback, context);
    return callback;
  };

  FirepadUserList.prototype.firebaseOff_ = function(ref, eventType, callback, context) {
    ref.off(eventType, callback, context);
    for(var i = 0; i < this.firebaseCallbacks_.length; i++) {
      var l = this.firebaseCallbacks_[i];
      if (l.ref === ref && l.eventType === eventType && l.callback === callback && l.context === context) {
        this.firebaseCallbacks_.splice(i, 1);
        break;
      }
    }
  };

  FirepadUserList.prototype.removeFirebaseCallbacks_ = function() {
    for(var i = 0; i < this.firebaseCallbacks_.length; i++) {
      var l = this.firebaseCallbacks_[i];
      l.ref.off(l.eventType, l.callback, l.context);
    }
    this.firebaseCallbacks_ = [];
  };


  /** DOM helpers */
  function elt(tag, content, attrs) {
    var e = document.createElement(tag);
    if (typeof content === "string") {
      setTextContent(e, content);
    } else if (content) {
      for (var i = 0; i < content.length; ++i) { e.appendChild(content[i]); }
    }
    for(var attr in (attrs || { })) {
      e.setAttribute(attr, attrs[attr]);
    }
    return e;
  }

  function setTextContent(e, str) {
    e.innerHTML = "";
    e.appendChild(document.createTextNode(str));
  }

  function on(emitter, type, f) {
    if (emitter.addEventListener) {
      emitter.addEventListener(type, f, false);
    } else if (emitter.attachEvent) {
      emitter.attachEvent("on" + type, f);
    }
  }

  function off(emitter, type, f) {
    if (emitter.removeEventListener) {
      emitter.removeEventListener(type, f, false);
    } else if (emitter.detachEvent) {
      emitter.detachEvent("on" + type, f);
    }
  }

  function preventDefault(e) {
    if (e.preventDefault) {
      e.preventDefault();
    } else {
      e.returnValue = false;
    }
  }

  function stopPropagation(e) {
    if (e.stopPropagation) {
      e.stopPropagation();
    } else {
      e.cancelBubble = true;
    }
  }

  function stopEvent(e) {
    preventDefault(e);
    stopPropagation(e);
  }

  return FirepadUserList;
})();
