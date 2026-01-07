import React, { useEffect, useState } from 'react';
import { View, Text, FlatList } from 'react-native';
import api from '../api';

export default function PlayersScreen() {
  const [players, setPlayers] = useState<any[]>([]);

  useEffect(() => {
    let mounted = true;
    api.get('/players').then(res => {
      if (mounted) setPlayers(res.data.players || res.data);
    }).catch(() => {});
    return () => { mounted = false; };
  }, []);

  return (
    <View style={{ flex:1, padding:16 }}>
      <Text style={{ fontSize:18, marginBottom:12 }}>Players</Text>
      <FlatList data={players} keyExtractor={p => String(p.id)} renderItem={({item}) => (
        <View style={{ padding:8, borderBottomWidth:1, borderColor:'#eee' }}>
          <Text style={{ fontWeight:'bold' }}>{item.name}</Text>
          <Text>{item.position || ''}</Text>
        </View>
      )} />
    </View>
  );
}
