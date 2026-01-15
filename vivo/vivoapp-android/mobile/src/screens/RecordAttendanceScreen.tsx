import React, { useEffect, useState } from 'react';
import { View, Text, Button, FlatList, TouchableOpacity } from 'react-native';
import api from '../api';

export default function RecordAttendanceScreen({ navigation }: any) {
  const [events, setEvents] = useState<any[]>([]);
  const [selectedEvent, setSelectedEvent] = useState<any | null>(null);
  const [players, setPlayers] = useState<any[]>([]);
  const [presentById, setPresentById] = useState<Record<number, boolean>>({});

  useEffect(() => {
    let mounted = true;
    api.get('/events').then(res => {
      const list = res.data.events || res.data;
      if (mounted) setEvents(list);
    }).catch(() => {});
    api.get('/players').then(res => {
      const list = res.data.players || res.data;
      if (mounted) setPlayers(list);
    }).catch(() => {});
    return () => { mounted = false; };
  }, []);

  const togglePlayer = (id:number) => {
    setPresentById(prev => ({ ...prev, [id]: !prev[id] }));
  };

  const submit = async () => {
    try {
      if (!selectedEvent) return;
      const selectedPlayers = players.filter(p => presentById[p.id]);
      // send attendance for each player
      await Promise.all(selectedPlayers.map(p => api.post('/attendance', { player_id: p.id, event_id: selectedEvent.id, status: 'present' })));
      // optionally send absent for the rest
      Alert.alert('Success', 'Attendance recorded');
      navigation.goBack();
    } catch (e:any) {
      Alert.alert('Record attendance failed', e.response?.data?.message || e.message);
    }
  };

  return (
    <View style={{ flex:1, padding:16 }}>
      <Text style={{ fontSize:18, marginBottom:12 }}>Record Attendance</Text>
      <Text style={{ marginBottom:8 }}>Select event</Text>
      <FlatList data={events} keyExtractor={e => String(e.id)} renderItem={({item}) => (
        <TouchableOpacity onPress={() => setSelectedEvent(item)} style={{ padding:8, borderBottomWidth:1, borderColor: selectedEvent?.id === item.id ? '#00f' : '#eee' }}>
          <Text style={{ fontWeight: selectedEvent?.id === item.id ? 'bold' : 'normal' }}>{item.title || item.name}</Text>
        </TouchableOpacity>
      )} style={{ maxHeight: 200, marginBottom: 12 }} />

      <Text style={{ marginBottom:8 }}>Mark present</Text>
      <FlatList data={players} keyExtractor={p => String(p.id)} renderItem={({item}) => (
        <TouchableOpacity onPress={() => togglePlayer(item.id)} style={{ padding:8, borderBottomWidth:1, borderColor:'#eee', flexDirection:'row', justifyContent:'space-between' }}>
          <Text>{item.name}</Text>
          <Text>{presentById[item.id] ? 'Present' : 'Absent'}</Text>
        </TouchableOpacity>
      )} />

      <View style={{ height: 12 }} />
      <Button title="Submit Attendance" onPress={submit} />
    </View>
  );
}
