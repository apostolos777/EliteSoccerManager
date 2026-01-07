import React, { useEffect, useState } from 'react';
import { View, Text, FlatList } from 'react-native';
import api from '../api';

export default function TeamsScreen() {
  const [teams, setTeams] = useState<any[]>([]);

  useEffect(() => {
    let mounted = true;
    api.get('/teams').then(res => {
      if (mounted) setTeams(res.data.teams || res.data);
    }).catch(() => {});
    return () => { mounted = false; };
  }, []);

  return (
    <View style={{ flex:1, padding:16 }}>
      <Text style={{ fontSize:18, marginBottom:12 }}>Teams</Text>
      <FlatList data={teams} keyExtractor={t => String(t.id)} renderItem={({item}) => (
        <View style={{ padding:8, borderBottomWidth:1, borderColor:'#eee' }}>
          <Text style={{ fontWeight:'bold' }}>{item.name}</Text>
          <Text>{item.age_group || ''}</Text>
        </View>
      )} />
    </View>
  );
}
