import React, { useEffect, useState } from 'react';
import { View, Text, FlatList } from 'react-native';
import api from '../api';

export default function EventsScreen() {
  const [events, setEvents] = useState<any[]>([]);

  useEffect(() => {
    let mounted = true;
    api.get('/events').then(res => {
      if (mounted) setEvents(res.data.events || res.data);
    }).catch(() => {});
    return () => { mounted = false; };
  }, []);

  return (
    <View style={{ flex:1, padding:16 }}>
      <Text style={{ fontSize:18, marginBottom:12 }}>Events</Text>
      <Button title="New Event" onPress={() => navigation.navigate('CreateEvent')} />
      <FlatList data={events} keyExtractor={e => String(e.id)} renderItem={({item}) => (
        <View style={{ padding:8, borderBottomWidth:1, borderColor:'#eee' }}>
          <Text style={{ fontWeight:'bold' }}>{item.title}</Text>
          <Text>{item.start || item.event_date}</Text>
        </View>
      )} style={{ marginTop:12 }} />
    </View>
  );
}
