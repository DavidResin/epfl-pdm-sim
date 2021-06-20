// Copyright 2015-2016, University of Colorado Boulder
// For licensing, please contact phethelp@colorado.edu

/**
 * Utilities for developing and using simulation wrappers.
 * @author Sam Reid (PhET Interactive Simulations)
 */
(function() {
  'use strict';

  /**
   * For the wrapper examples, determine which simulation to use.
   * Defaults to 'concentration', can be overriden by the value passed in to this function,
   * which, in turn, can be overriden by a query parameter ?sim
   */
  var getSimName = function( simName ) {
    simName = QueryStringMachine.get( 'sim', { type: 'string', defaultValue: simName } );
    if ( simName.charAt( 0 ) === '$' ) {
      throw new Error( 'variable was not filled in, specify in getSim call or in ?sim= query parameter' );
    }
    return simName;
  };

  // Adapted from Stack Overflow, see http://stackoverflow.com/questions/25085306/javascript-space-separated-string-to-camelcase
  function toCamelCase( string ) {
    var out = '';

    // Add whitespace after each digit so that strings like myString1pattern will get camelcased with uppercase P
    var withWhitespaceAfterDigits = string.replace( /\d/g, function( a ) {return a + ' ';} ).trim();

    // Split on whitespace, remove whitespace and uppercase the first word in each term
    withWhitespaceAfterDigits.split( '-' ).forEach( function( element, index ) {
      out += (index === 0 ? element : element[ 0 ].toUpperCase() + element.slice( 1 ));
    } );

    // lowercase the first character
    if ( out.length > 1 ) {
      out = out.charAt( 0 ).toLowerCase() + out.slice( 1 );
    }
    else if ( out.length === 1 ) {
      out = out.toLowerCase();
    }

    return out;
  }

  /**
   * This method requires hackery to get things working for the compiled versions.
   * @param defaultSimName
   * @param version
   * @returns {string}
   * TODO: This looks like it should be simplified
   */
  var getURL = function( defaultSimName, version ) {

    var simName = getSimName( defaultSimName );

    // Used for testing a localhost copy of build/phet-io/wrappers before deploying
    if ( QueryStringMachine.get( 'launchLocalVersion', { type: 'flag' } ) ) {

      // The dummy query parameter just makes it easier to augment with & for further args
      return '../../' + simName + '_en-phetio.html?production';
    }

    // Templated strings are enclosed in {{}} by PhET convention
    if ( version.substring( 0, 2 ) !== '{{' ) {
      return 'https://phet-io.colorado.edu/sims/' + simName + '/' + version + '/' + simName + '_en-phetio.html?production';
    }

    if ( !QueryStringMachine.containsKey( 'sim' ) ) {

      // The dummy query parameter just makes it easier to augment with & for further args
      return '../../' + simName + '_en-phetio.html?production';
    }
    else {

      // requirejs version, runs with assertions enabled
      return '../../../' + simName + '/' + simName + '_en.html?brand=phet-io';
    }
  };

  window.WrapperUtils = {

    /**
     * @param {string} name - the lowercase, hyphenated sim name, such as beers-law-lab
     * @param {string} version - the sim version
     *
     * If template variables such as beers-law-lab and 1.6.15-phetio are used, then
     * query parameters are used to look up the sim.  This is to support both the requirejs version and built versions.
     */
    getSim: function( name, version ) {

      name = getSimName( name );
      var URL = getURL( name, version );
      return {
        name: name,
        URL: URL,
        camelCaseName: toCamelCase( name )
      };
    }
  };
})();