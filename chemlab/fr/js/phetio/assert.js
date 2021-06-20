// Copyright 2014-2016, University of Colorado Boulder

/**
 * This function adds window.assert for usage in PhET-iO wrappers.
 * TODO: replace with assert/js/assert.js
 *
 * @author Sam Reid
 */
(function() {
  'use strict';

  window.assert = function( b, message ) {
    if ( !b ) {
      console.log( message );
      throw new Error( message );
    }
  };
})();