import React, { useEffect, useState } from 'react';
import { View, Text, FlatList } from 'react-native';
import api from '../api';

export default function AttendanceScreen() {
  const [attendance, setAttendance] = useState<any[]>([]);

  useEffect(() => {
    let mounted = true;
    // Fetch attendance for latest event as simple default
    api.get('/events').then(res => {
      const events = res.data.events || res.data;
      const first = events && events.length ? events[0] : null;
      if (first) {
        api.get(`/attendance/event/${first.id}`).then(r => {
          if (mounted) setAttendance(r.data.attendance || r.data);
        }).catch(() => {});
      }
    }).catch(() => {});
    return () => { mounted = false; };
  }, []);

  return (
    <View style={{ flex:1, padding:16 }}>
      <Text style={{ fontSize:18, marginBottom:12 }}>Attendance</Text>
      <FlatList data={attendance} keyExtractor={a => String(a.id)} renderItem={({item}) => (
        <View style={{ padding:8, borderBottomWidth:1, borderColor:'#eee' }}>
          <Text style={{ fontWeight:'bold' }}>{item.player_id}</Text>
          <Text>{item.status || ''}</Text>
        </View>
      )} />
    </View>
  );
}
