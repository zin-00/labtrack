export function useRealTimeEvents() {
  
  // Check if Echo is available and connected
  const isEchoReady = () => {
    if (!window.Echo) {
      console.error('Echo is not initialized');
      return false;
    }
    return true;
  };

  // Generic subscribe method
  const subscribeToChannel = (channelName, eventName, handler) => {
    if (!isEchoReady()) return;

    try {
      window.Echo.channel(channelName).listen(eventName, handler);
      console.log(`✓ Subscribed to ${channelName} - ${eventName}`);
    } catch (error) {
      console.error(`✗ Failed to subscribe to ${channelName}:`, error);
    }
  };

  // Generic update handler (merge or add)
  const handleEventUpdate = (list, data) => {
    if (!list.value) {
      console.error('List is not a valid ref');
      return;
    }

    const index = list.value.findIndex(item => item.id === data.id);

    if (index !== -1) {
      // Update existing item
      const updatedData = { ...list.value[index], ...data };
      list.value.splice(index, 1, updatedData);
      console.log('✓ Updated existing item:', data.id);
    } else {
      // Add new item if not found
      list.value.unshift(data);
      console.log('✓ Added new item:', data.id);
    }
  };

  // Register multiple event listeners
  const register = (configs = []) => {
    if (!isEchoReady()) {
      console.error('Cannot register events: Echo not available');
      return;
    }

    console.log('📡 Registering event listeners...');

    configs.forEach(cfg => {
      console.log(`Registering: ${cfg.event} on ${cfg.channel}`);
      
      try {
        const channel = window.Echo.channel(cfg.channel);
        
        // Listen for the event (remove the dot prefix)
        const eventName = cfg.event.startsWith('.') ? cfg.event.slice(1) : cfg.event;
        
        channel.listen(eventName, (e) => {
          console.log(`🔔 Event received: ${eventName}`, e);
          
          // Call the handler
          if (typeof cfg.OnReceive === 'function') {
            cfg.OnReceive(e);
          } else {
            console.error(`OnReceive handler not found for ${eventName}`);
          }
        });
        
        console.log(`✓ Successfully registered ${eventName} on ${cfg.channel}`);
      } catch (error) {
        console.error(`✗ Failed to register ${cfg.event}:`, error);
      }
    });
  };

  // Unsubscribe from a channel
  const unsubscribe = (channelName) => {
    if (!isEchoReady()) return;
    
    try {
      window.Echo.leave(channelName);
      console.log(`✓ Unsubscribed from ${channelName}`);
    } catch (error) {
      console.error(`✗ Failed to unsubscribe from ${channelName}:`, error);
    }
  };

  return {
    subscribeToChannel,
    handleEventUpdate,
    register,
    unsubscribe,
    isEchoReady
  };
}