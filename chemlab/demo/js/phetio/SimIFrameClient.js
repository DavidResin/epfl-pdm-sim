// Copyright 2015-2016, University of Colorado Boulder
// For licensing, please contact phethelp@colorado.edu

/**
 * Client API for connection to simulations, see https://github.com/phetsims/phet-io/tree/gh-pages
 * @author Sam Reid (PhET Interactive Simulations)
 */
(function() {
  'use strict';

  // The protocol version supported by this client, so that we can maintain backward compatibility when making
  // protocol changes (or at least send reasonable error messages)
  var protocol = 'phet-io-0.0.1';

  var functionHandles = {};

  /**
   * This SimIFrameClient communicates to one and only one iframe.  Dispose it when you are done with it.
   * There is not any support for having two SimIFrameClients connect to the same sim iframe (as of April 8, 2015)
   * @constructor
   */
  window.SimIFrameClient = function( frame ) {

    // Keep track of message unique IDs for matching responses.
    // Don't start at 0 or it won't pass the message.request.messageID test below!
    var messageID = 1;

    // When sending a message, the system can watch for responses that match the incoming messageID
    var responseListeners = [];

    // General listeners that just want to see what message was received, not necessarily in response to any request
    var messageListeners = [];

    var nextMessageIndex = 0;

    var dispatch = function( message ) {
      if ( message.functionHandle ) {
        functionHandles[ message.functionHandle ].apply( undefined, message.args );
      }
      for ( var i = 0; i < responseListeners.length; i++ ) {
        if ( message.request && message.request.messageID && responseListeners[ i ].message.messageID === message.request.messageID ) {

          // Check for the invokeSequence case first, which replies with 'returnValues'
          if ( message.returnValues ) {

            // The callback is optional--only call the callback if it is defined
            responseListeners[ i ].callback && responseListeners[ i ].callback.apply( null, message.returnValues );
          }
          else {
            responseListeners[ i ].callback( message.returnValue );
          }

          // TODO: We need a way to remove listeners that are used only once, perhaps a return value from the method
          // Some responseListeners need to receive multiple callbacks.  Others should be removed.
          //if ( responseListeners[ i ].message.command !== 'addPhETIOEventsListener' ) {
          //  responseListeners.splice( i, 1 );
          //}
          return; // Only one listener per message ID, though other listeners could be added elsewhere using addEventListener.
        }
      }

      for ( i = 0; i < messageListeners.length; i++ ) {
        messageListeners[ i ]( message );
      }
    };

    // See http://stackoverflow.com/questions/3710204/how-to-check-if-a-string-is-a-valid-json-string-in-javascript-without-using-try
    function tryParseJSON( jsonString ) {
      try {
        var o = JSON.parse( jsonString );

        // Handle non-exception-throwing cases:
        // Neither JSON.parse(false) or JSON.parse(1234) throw errors, hence the type-checking,
        // but... JSON.parse(null) returns null, and typeof null === "object",
        // so we must check for that, too. Thankfully, null is falsey, so this suffices:
        if ( o && typeof o === 'object' ) {
          return o;
        }
      }
      catch( e ) { // eslint-disable-line
      }

      return false;
    }

    // The postMessage specification does not require messages to be delivered in increasing order.  Keep a buffer
    // of the messages in case they arrive out of numerical order.
    var messages = [];

    var windowMessageListener = function( event ) {

      // Create a plain JS object from the message string, so we can easily query it
      var message = tryParseJSON( event.data );

      // Make sure the message came from the frame we are interested in, perhaps event.source
      if ( message && event.source === frame.contentWindow && message.protocol && message.protocol.indexOf( 'phet-io-' ) === 0 ) {

        if ( message.protocol !== 'phet-io-0.0.1' ) {
          console.log( 'Protocol mismatch: ' + message.protocol + ', currently supports phet-io-0.0.1' );
        }

        // Add the message to the queue
        messages.push( message );

        // Process messages in numerical order, starting with the message we just received, see https://github.com/phetsims/phet-io/issues/796
        for ( var i = messages.length - 1; i >= 0; i-- ) {
          if ( messages[ i ].postMessageIndex === nextMessageIndex ) {
            dispatch( messages[ i ] );
            nextMessageIndex = messages[ i ].postMessageIndex + 1;
            messages.splice( i, 1 ); // Remove from the array
            i = messages.length; // Start one past the end again to restart the loop to see if other messages can be handled.
          }
        }

        assert && assert( messages.length < 1000, 'too many out of order messages' );
      }
      else {
        // Received a message from some other frame.
      }

    };
    // Listen for events from the simulation iframe
    // TODO: Adding two SimIFrameClients for the same frame would cause confusion--they would both receive the messages and perhaps the index counters would mismatch?
    window.addEventListener( 'message', windowMessageListener, false );
    return {
      frame: frame,

      /**
       * If you need to send multiple messages or if the order of messages matters, then use invokeSequence.
       * callback is called when the entire sequence is complete.
       *
       * @param sequence - { phetioID:string, method:string, [args:objects:optional] }
       * @param callback - function
       */
      invokeSequence: function( sequence, callback ) {
        var messages = [];
        for ( var i = 0; i < sequence.length; i++ ) {
          var item = sequence[ i ];
          var m = this.wrap( item.phetioID, item.method, item.args );
          messages.push( m );
        }

        var message = {
          messageID: messageID,
          protocol: protocol,
          messages: messages
        };
        responseListeners.push( { message: message, callback: callback } );
        frame.contentWindow.postMessage( JSON.stringify( message ), '*' );
        messageID++;
      },

      /**
       * Send the specified JS object with the protocol and as JSON
       * This modifies the message by augmenting it with messageID and protocol.  Messages should not be re-used
       * @param {string} phetioID
       * @param {string} method
       * @param {Object[]} [args] - optional args
       * @param {function} [callback] - optional callback
       */
      invoke: function( phetioID, method, args, callback ) {
        var message = this.wrap( phetioID, method, args, callback );
        if ( callback ) {
          responseListeners.push( { message: message, callback: callback } );
        }
        frame.contentWindow.postMessage( JSON.stringify( message ), '*' );
        messageID++;
      },

      wrap: function( phetioID, method, args ) {
        var message = {
          phetioID: phetioID,
          method: method,
          args: args,
          messageID: messageID,
          protocol: protocol
        };

        // replace function args with wrappers
        if ( message.args ) {
          for ( var i = 0; i < message.args.length; i++ ) {
            var arg = message.args[ i ];
            if ( typeof arg === 'function' ) {
              var key = 'functionHandle@messageID=' + message.messageID + ', argIndex=' + i;

              // switcheroo
              functionHandles[ key ] = message.args[ i ];
              message.args[ i ] = key;
            }
          }
        }
        return message;
      },

      addMessageListener: function( messageListener ) {
        messageListeners.push( messageListener );
      },
      removeMessageListener: function( messageListener ) {
        var index = messageListeners.indexOf( messageListener );
        if ( index !== -1 ) {
          messageListeners.splice( index, 1 );
        }
      },
      dispose: function() {
        window.removeEventListener( 'message', windowMessageListener, false );
      },
      onSimInitialized: function( callback ) {
        // TODO: Should we remove this after it fired?  What if the sim was already initialized before we called this?
        this.addMessageListener( function( message ) {
          if ( message.command === 'simInitialized' ) {
            callback( message );
          }
        } );
      },

      onPhETiOInitialized: function( callback ) {

        // TODO: Should we remove this after it fired?  What if the sim was already initialized?
        this.addMessageListener( function( message ) {
          if ( message.command === 'simIFrameAPIInitialized' ) {
            callback( message );
          }
        } );
      },

      /**
       * Launch the simulation with the specified options, see below for options documentation:
       * @param {string} URL - the URL of the simulation to launch
       * @param {Object} [options], keys and defaults specified below
       */
      launchSim: function( URL, options ) {

        options = options || {};

        options.passThroughQueryParameters = options.hasOwnProperty( 'passThroughQueryParameters' ) ? options.passThroughQueryParameters : true;
        options.emitStates = options.emitStates || false;
        options.emitInputEvents = options.emitInputEvents || false;
        options.phetioEventsListener = options.phetioEventsListener || null; // {function} called when a message is produced by PhET-iO
        options.callback = options.callback || function() {}; // {function} called after launch is called

        // {Object[]} an array of {phetioID,method,args} that specifies PhET-iO calls to make before the sim launches
        // These customizations will be applied as the sim starts up.
        options.expressions = options.expressions || [];
        options.onSimInitialized = options.onSimInitialized || function() {};
        options.onPhETiOInitialized = options.onPhETiOInitialized || function() {};

        // Allow query parameter overrides for flexibility, so that the same wrapper can support multiple modes
        // without having to be republished
        options.emitStates = QueryStringMachine.get( 'phetioEmitStates', {
          type: 'boolean',
          defaultValue: options.emitStates
        } );
        options.emitInputEvents = QueryStringMachine.get( 'phetioEmitInputEvents', {
          type: 'boolean',
          defaultValue: options.emitInputEvents
        } );

        this.onSimInitialized( options.onSimInitialized );

        // When the sim launches, wire up a listener to the phetioEvents messages
        var self = this;

        var launch = function() {

          // Expressions that will lie in wait, then take effect as components are created.
          self.invoke( 'phetio', 'addExpressions', [ options.expressions ], function() {
            self.invoke( 'phetio', 'launchSimulation', [], options.callback );
          } );
        };
        this.onPhETiOInitialized( function() {
          options.onPhETiOInitialized();
          if ( options.phetioEventsListener ) {
            self.invoke( 'phetio', 'addPhETIOEventsListener', [ options.phetioEventsListener ], launch );
          }
          else {
            launch();
          }
        } );

        // Avoid duplicating these keys in the URL, if they already exist.
        var src = URL +
                  (QueryStringMachine.containsKey( 'phetioEmitStates' ) ? '' : '&phetioEmitStates=' + options.emitStates) +
                  (QueryStringMachine.containsKey( 'phetioEmitInputEvents' ) ? '' : '&phetioEmitInputEvents=' + options.emitInputEvents);

        // Pass through all Query Parameters.
        if ( options.passThroughQueryParameters && window.location.search.length > 0 ) {
          src = src + '&' + window.location.search.substring( 1 );
        }

        // Start loading the sim in the iframe
        self.frame.src = src;
      }
    };
  };
})();