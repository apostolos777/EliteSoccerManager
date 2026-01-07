import React, { useEffect, useState } from 'react';
import { View, Text, TextInput, Button, Alert, Picker } from 'react-native';
import api from '../api';

export default function CreatePlayerScreen({ navigation }: any) {
  const [name, setName] = useState('');
  const [position, setPosition] = useState('');
  const [teamId, setTeamId] = useState<number | null>(null);
  const [teams, setTeams] = useState<any[]>([]);

  useEffect(() => {
    let mounted = true;
    api.get('/teams').then(res => {
      const list = res.data.teams || res.data;
      if (mounted) setTeams(list);
    }).catch(() => {});
    return () => { mounted = false; };
  }, []);

  const submit = async () => {
    try {
      const payload: any = { name, position };
      if (teamId) payload.team_id = teamId;
      const res = await api.post('/players', payload);
      Alert.alert('Success', 'Player created');
      navigation.goBack();
    } catch (e:any) {
      Alert.alert('Create player failed', e.response?.data?.message || e.message);
    }
  };

  return (
    <View style={{ flex:1, padding:16 }}>
      <Text style={{ fontSize:18, marginBottom:12 }}>Create Player</Text>
      <TextInput placeholder="Name" value={name} onChangeText={setName} style={{ borderWidth:1, padding:8, marginBottom:8 }} />
      <TextInput placeholder="Position" value={position} onChangeText={setPosition} style={{ borderWidth:1, padding:8, marginBottom:8 }} />
      <Text style={{ marginBottom: 6 }}>Team</Text>
      {/* Simple Picker, fallback to text if not supported */}
      <Picker selectedValue={teamId} onValueChange={(v) => setTeamId(Number(v))}>
        <Picker.Item label="(none)" value={0} />
        {teams.map(t => <Picker.Item key={t.id} label={t.name} value={t.id} />)}
      </Picker>
      <View style={{ height: 12 }} />
      <Button title="Create" onPress={submit} />
    </View>
  );
}
