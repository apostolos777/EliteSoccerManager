import React from 'react';
import { NavigationContainer } from '@react-navigation/native';
import { createNativeStackNavigator } from '@react-navigation/native-stack';
import { Text, View, Button } from 'react-native';
import LoginScreen from './src/screens/LoginScreen';
import TeamsScreen from './src/screens/TeamsScreen';
import PlayersScreen from './src/screens/PlayersScreen';
import EventsScreen from './src/screens/EventsScreen';
import AttendanceScreen from './src/screens/AttendanceScreen';
import { clearToken } from './src/api';

const Stack = createNativeStackNavigator();

function HomeScreen({ navigation }: any) {
  return (
    <View style={{ flex: 1, alignItems: 'center', justifyContent: 'center', padding: 16 }}>
      <Text style={{ fontSize: 18, marginBottom: 12 }}>Vivo Mobile - Home</Text>
      <Button title="Teams" onPress={() => navigation.navigate('Teams')} />
      <View style={{ height: 8 }} />
      <Button title="Players" onPress={() => navigation.navigate('Players')} />
      <View style={{ height: 8 }} />
      <Button title="Events" onPress={() => navigation.navigate('Events')} />
      <View style={{ height: 8 }} />
      <Button title="Attendance" onPress={() => navigation.navigate('Attendance')} />
      <View style={{ height: 20 }} />
      <Button title="Logout" color="red" onPress={async () => { await clearToken(); navigation.replace('Login'); }} />
    </View>
  );
}

export default function App() {
  return (
    <NavigationContainer>
      <Stack.Navigator initialRouteName="Login">
        <Stack.Screen name="Login" component={LoginScreen} options={{ title: 'Sign In' }} />
        <Stack.Screen name="Home" component={HomeScreen} />
        <Stack.Screen name="Teams" component={TeamsScreen} />
        <Stack.Screen name="Players" component={PlayersScreen} />
        <Stack.Screen name="Events" component={EventsScreen} />
        <Stack.Screen name="Attendance" component={AttendanceScreen} />
      </Stack.Navigator>
    </NavigationContainer>
  );
}
