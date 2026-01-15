import React, { useState } from 'react';
import { View, Text, TextInput, Button, Alert } from 'react-native';
import api from '../api';

export default function CreateTeamScreen({ navigation }: any) {
  const [name, setName] = useState('');
  const [ageGroup, setAgeGroup] = useState('');
  const [contactEmail, setContactEmail] = useState('');

  const submit = async () => {
    try {
      const res = await api.post('/teams', { name, age_group: ageGroup, contact_email: contactEmail });
      Alert.alert('Success', 'Team created');
      navigation.goBack();
    } catch (e:any) {
      Alert.alert('Create team failed', e.response?.data?.message || e.message);
    }
  };

  return (
    <View style={{ flex:1, padding:16 }}>
      <Text style={{ fontSize:18, marginBottom:12 }}>Create Team</Text>
      <TextInput placeholder="Name" value={name} onChangeText={setName} style={{ borderWidth:1, padding:8, marginBottom:8 }} />
      <TextInput placeholder="Age group" value={ageGroup} onChangeText={setAgeGroup} style={{ borderWidth:1, padding:8, marginBottom:8 }} />
      <TextInput placeholder="Contact email" value={contactEmail} onChangeText={setContactEmail} style={{ borderWidth:1, padding:8, marginBottom:12 }} keyboardType="email-address" />
      <Button title="Create" onPress={submit} />
    </View>
  );
}
