/*
 *
 *      HSP Postline
 *
 *      chat frame refresh
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
 *      performs a simpler request in GET mode, and directs output to the appropriate callback
 *
 */

function chRefresh () {

        var fsRows, cLines;

        if (document.layers) {                          // Netscape, Firefox, probably deprecated

                fsRows = parent.parent.document.layers['fset'].rows;

        }

        else {

                if (document.all) {                     // MSIE, but acknowledged by Opera as well

                        eval ('fsRows = parent.parent.document.all.fset.rows');

                }

                else {

                        if (document.getElementById) {  // anything else, standard

                                fsRows = parent.parent.document.getElementById('fset').rows;

                        }

                }

        }

        fsRows = fsRows.split (',');
        cLines = (fsRows[1]) ? fsRows[1].valueOf() : 96;
        cLines = Math.round (cLines / 16);

        dLoad ('../../chrefresh.php?cLines=' + cLines.toString(), false, false, doRefresh);

}

/*
 *
 *      upon successful completion of chRefresh (), loads chat frame with response,
 *      and starts a timer to refresh again every 3 seconds
 *
 */

function doRefresh (response) {

        var conversation;

        if (response) {

                conversation = response.crop ('/conversation').toString();
                conversation = Base64.decode (conversation);

                setProp ('conversation', 'innerHTML', conversation);

        }

        else {

                // setProp ('conversation', 'innerHTML', dLoadError);

        }

        setTimeout ('chRefresh ()', 3000);

}
