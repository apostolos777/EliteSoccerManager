import React, { useState } from 'react';
import { View, Text, TextInput, Button, Alert } from 'react-native';
import api from '../api';

export default function CreateEventScreen({ navigation }: any) {
  const [title, setTitle] = useState('');
  const [eventDate, setEventDate] = useState('');

  const submit = async () => {
    try {
      const res = await api.post('/events', { title, event_date: eventDate });
      Alert.alert('Success', 'Event created');
      navigation.goBack();
    } catch (e:any) {
      Alert.alert('Create event failed', e.response?.data?.message || e.message);
    }
  };

  return (
    <View style={{ flex:1, padding:16 }}>
      <Text style={{ fontSize:18, marginBottom:12 }}>Create Event</Text>
      <TextInput placeholder="Title" value={title} onChangeText={setTitle} style={{ borderWidth:1, padding:8, marginBottom:8 }} />
      <TextInput placeholder="Event date (YYYY-MM-DD)" value={eventDate} onChangeText={setEventDate} style={{ borderWidth:1, padding:8, marginBottom:12 }} />
      <Button title="Create" onPress={submit} />
    </View>
  );
}
