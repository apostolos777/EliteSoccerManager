import React, { useState } from 'react';
import { View, Text, TextInput, Button, Alert } from 'react-native';
import api, { setToken } from '../api';

export default function LoginScreen({ navigation }: any) {
  const [email, setEmail] = useState('');
  const [password, setPassword] = useState('');

  const submit = async () => {
    try {
      const res = await api.post('/auth/login', { email, password });
      const token = res.data.token;
      if (token) {
        await setToken(token);
        navigation.replace('Home');
      } else {
        Alert.alert('Login failed', 'No token received');
      }
    } catch (e:any) {
      Alert.alert('Login failed', e.response?.data?.message || e.message);
    }
  };

  return (
    <View style={{ flex:1, justifyContent:'center', padding:16 }}>
      <Text style={{ fontSize:18, marginBottom:12 }}>Vivo - Login</Text>
      <TextInput placeholder="Email" value={email} onChangeText={setEmail} style={{ borderWidth:1, marginBottom:8, padding:8 }} />
      <TextInput placeholder="Password" value={password} onChangeText={setPassword} secureTextEntry style={{ borderWidth:1, marginBottom:12, padding:8 }} />
      <Button title="Login" onPress={submit} />
    </View>
  );
}
