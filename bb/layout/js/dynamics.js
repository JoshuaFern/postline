/*
 *
 *      HSP Postline
 *
 *      AJAX dynamics
 *      version 2010.02.10 (10.2.10)
 *
 *                      Copyright (C) 2003-2010 by Alessandro Ghignola
 *                      Copyright (C) 2003-2010 Home Sweet Pixel software
 *
 *      This program is free software; you can redistribute it and/or modify
 *      it under the terms of the GNU General Public License as published by
 *      the Free Software Foundation; either version 2 of the License, or
 *      (at your option) any later version.
 *
 *      This program is distributed in the hope that it will be useful,
 *      but WITHOUT ANY WARRANTY; without even the implied warranty of
 *      MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *      GNU General Public License for more details.
 *
 *      You should have received a copy of the GNU General Public License
 *      along with this program; if not, write to the Free Software
 *      Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA  02110-1301, USA
 *
 */

/*
 *
 *      common variables to getProp, setProp, setFocus
 *
 */

var iTarget = null;
var unescapeOpen = unescapeClose = '';

/*
 *
 *      gets value of property 'prop' of element 'elem' (referred by id)
 *
 */

function getProp (elem, prop) {

  if (document.layers) {                                        // Netscape/Firefox, probably deprecated

    return eval ('document.layers[\'' + elem + '\'].' + prop);

  }
  else {

    if (document.all) {                                         // MSIE, but acknowledged by Opera as well

      return eval ('document.all.' + elem + '.' + prop);

    }
    else {

      if (document.getElementById) {                            // standard

        iTarget = document.getElementById (elem);

        if (iTarget) {

          return eval ('iTarget.' + prop);

        }

      }

    }

  }

}

/*
 *
 *      sets property 'prop' of element 'elem' (referred by id) to given value 'val'
 *
 */

function setProp (elem, prop, val) {

  if (typeof val == 'string') {

    val = '\'' + escape (val) + '\'';
    unescapeOpen = 'unescape (';
    unescapeClose = ')';

  }
  else {

    unescapeOpen = '';
    unescapeClose = '';

  }

  if (document.layers) {                                        // Netscape/Firefox, probably deprecated

    eval ('document.layers[\'' + elem + '\'].' + prop + '=' + unescapeOpen + val + unescapeClose);

  }
  else {

    if (document.all) {                                         // MSIE, but acknowledged by Opera as well

      eval ('document.all.' + elem + '.' + prop + '=' + unescapeOpen + val + unescapeClose);

    }
    else {

      if (document.getElementById) {                            // standard

        iTarget = document.getElementById (elem);

        if (iTarget) {

          eval ('iTarget.' + prop + '=' + unescapeOpen + val + unescapeClose);

        }

      }

    }

  }

}

function setFocus (elem) {

  if (document.layers) {                                        // Netscape/Firefox, probably deprecated

    eval ('document.layers[\'' + elem + '\'].focus ()');

  }
  else {

    if (document.all) {                                         // MSIE, but acknowledged by Opera as well

      eval ('document.all.' + elem + '.focus ()');

    }
    else {

      if (document.getElementById) {                            // standard

        iTarget = document.getElementById (elem);

        if (iTarget) {

          eval ('iTarget.focus()');

        }

      }

    }

  }

}

/*
 *
 *      load dynamic content (dLoad):
 *
 *      given a server-side script URL as 'url', an array of argument names 'args',
 *      which length is the same as the array of values 'values', and a function to
 *      be called on successful request, 'dLoad' will:
 *
 *      - URLencode argument values and assemble arguments into an HTTP request's content
 *      - POST the request to the server
 *      - await a response
 *      - parse the response as XML
 *      - return the parsed documentElement object to the callback function, as its first argument
 *
 *      on error, returns false to the callback function
 *
 */

var lastRequest = false;
var dLoadError = false;

function dLoad (url, args, values, cback) {

  var i, requestContent, xmlHttp, xmldom, xmldomparser, response = false;
  var pairs = new Array ();

  if (lastRequest != cback) {

    lastRequest = cback;
    dLoadError = false;

  }
  else {

    alert ('The request is already being processed. Please wait, thank you.');
    return;

  }

  if ((args) && (values) && (args.length == values.length)) {

    for (i = 0; i < args.length; i ++) {

      pairs.push (args[i] + '=' + escape (values[i]));

    }

  }

  requestContent = pairs.join ('&');

  try {

    xmlHttp = new XMLHttpRequest ();                            // standard

  }
  catch (e) {

    try {

      xmlHttp = new ActiveXObject ("Msxml2.XMLHTTP");           // MSIE 6

    }
    catch (e) {

      try {

        xmlHttp = new ActiveXObject ("Microsoft.XMLHTTP");      // MSIE 5.0

      }
      catch (e) {

        dLoadError = 'AJAX dynamics not supported';
        cback (false);
        lastRequest = false;
        return;

      }

    }

  }

  try {

    xmlHttp.open ("POST", url, true);                           // set request

  }
  catch (e) {

    dLoadError = 'failed opening HTTP request';
    cback (false);
    lastRequest = false;
    return;

  }

  xmlHttp.setRequestHeader ("Content-type", "application/x-www-form-urlencoded");
  xmlHttp.setRequestHeader ("Content-length", requestContent.length);
  xmlHttp.setRequestHeader ("Connection", "close");

  xmlHttp.onreadystatechange = function () {                    // process readystatechange

    if (xmlHttp.readyState == 4) {                              // if completed

      if (xmlHttp.status == 200) {                              // if status 200 (OK)

        if (window.ActiveXObject) {                             // MSIE

          xmldom = new ActiveXObject ("Microsoft.XMLDOM");

          xmldom.async = "false";
          xmldom.loadXML (xmlHttp.responseText);

        }
        else {                                                  // standard

          xmldomparser = new DOMParser ();
          xmldom = xmldomparser.parseFromString (xmlHttp.responseText, "text/xml");

        }

        if (xmldom) {

          response = new xmlResponse (xmldom.documentElement);

          dLoadError = false;
          cback (response);
          lastRequest = false;

        }
        else {

          dLoadError = 'failed parsing XML data';
          cback (false);
          lastRequest = false;

        }

      }
      else {

        dLoadError = 'server error (code ' + xmlHttp.status + ')';
        cback (false);
        lastRequest = false;

      }

    }

  }

  try {

    xmlHttp.send (requestContent);                              // send request

  }
  catch (e) {

    dLoadError = 'failed sending HTTP request';
    cback (false);
    lastRequest = false;

  }

}

/*
 *
 *      utilities to handle traversing of XML trees:
 *      the dLoad function in fact passes one of these xmlResponse objects to its callback
 *
 *      the 'subSetSelect' method allows to explore a single, and unique, branch of the XML tree,
 *      and will "restrict" the search range for when method 'crop' is subsequently applied: as
 *      long as you have such unique branches (ie. tags which don't appear more than once in their
 *      parent tag), you can use 'subSetSelect' to speed up extraction of data from the XML tree;
 *      if no such subset exists, this method returns false
 *
 *      the 'crop' method is what allows to retrieve any data fields, eventually in multiple
 *      occurrences, from the XML tree; this alone is sufficient to traverse the whole tree, but
 *      using it in conjunction to 'subSetSelect' will increase preformance significantly; 'crop'
 *      returns an array of strings holding all found occurrences of the requested data field
 *
 *      for example, this will display all moderators' names from the output of 'authlist':
 *
 *        alert (myResponseObject.crop ('/staff/moderator/name'));
 *
 *      ...by starting from root node (/), then looking into 'staff', which is a proper subset,
 *      other than a child node of the root, and finally cropping all values of 'name' nodes which
 *      are contained in 'moderator' branches; but since 'staff' is a unique branch appearing only
 *      once in the root node, it can be defined as a subset of the root, and this other example:
 *
 *        if (myResponseObject.subSetSelect ('/staff')) {
 *
 *          alert (myResponseObject.crop ('moderator/name'));
 *
 *        }
 *
 *      ...will speed things up considerably, since the "search" is limited to the 'staff' subset,
 *      and will not involve fields from, for instance, the 'hierarchy' subset, or any other nodes
 *      of the document element of authlist's output being placed outside the 'staff' subset
 *
 *      note that the syntax for the 'path' argument to both methods mimics that of a file system,
 *      with branches of the tree as folders and fields' data as files; in this point of view, the
 *      current work folder exactly corresponds to the latest selected subset, while prepending a
 *      single slash to the path makes the path "absolute", meaning it refers to the root node; if
 *      you need two or more "croppings" from different subsets, you HAVE to change subset between
 *      the two calls to 'crop', exactly as you would while navigating a file system's directories;
 *      for the sake of completeness, then myResponseObject.subSetSelect ('/') corresponds to "cd/"
 *
 */

function xmlResponse (responseDocumentElement) {

  this.responseDocumentElement = responseDocumentElement;       // symbol
  this.currentSubSet = responseDocumentElement;                 // symbol
  this.subSetSelect = xmlResponseSubSetSelect;                  // method
  this.crop = xmlResponseCrop;                                  // method

}

function xmlResponseSubSetSelect (path) {

  var i, j, f, p, r;

  if (path == '/') {

    this.currentSubSet = this.responseDocumentElement;

  }
  else {

    p = path.split ('/');
    r = this.responseDocumentElement;

    if (p[0].length) {

      r = this.currentSubSet;

    }
    else {

      p = p.slice (1);

    }

    for (i = 0; i < p.length; i ++) {

      f = false;

      for (j = 0; j < r.childNodes.length; j ++) {

        if (r.childNodes[j].nodeName == p[i]) {

          f = true;
          r = r.childNodes[j];
          break;

        }

      }

      if (!f) {

        return false;

      }

    }

    this.currentSubSet = r;

  }

  return true;

}

function xmlResponseFilterBranch (r, p, f) {

  var i, j;
  var q = new Array ();

  if (p.length) {

    for (i = 0; i < r.childNodes.length; i ++) {

      if (r.childNodes[i].nodeName == p[0]) {

        q = q.concat (xmlResponseFilterBranch (r.childNodes[i], p.slice (1), f));

      }

    }

  }
  else {

    for (i = 0; i < r.childNodes.length; i ++) {

      if (r.childNodes[i].nodeName == f) {

        v = r.childNodes[i].childNodes[0].nodeValue;

        for (j = 0; j < v.length; j ++) {

          if (v.charCodeAt (j) > 32) {

            v = v.slice (j);
            break;

          }

        }

        for (j = v.length - 1; j >= 0; j --) {

          if (v.charCodeAt (j) > 32) {

            v = v.slice (0, j + 1);
            break;

          }

        }

        q.push (v);

      }

    }

  }

  return q;

}

function xmlResponseCrop (path) {

  var p = path.split ('/');
  var r = this.responseDocumentElement;

  if (p[0].length) {

    r = this.currentSubSet;

  }
  else {

    p = p.slice (1);

  }

  return xmlResponseFilterBranch (r, p, p.pop ());

}
