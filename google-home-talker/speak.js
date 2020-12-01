const GoogleHomeTalk = require('./GoogleHomeTalk');

const ght = new GoogleHomeTalk();

const testing = 'https://www.dropbox.com/s/z6yn25fdi4fdv2q/testing.mp3?dl=1';
const food = 'https://www.dropbox.com/s/zskaqzwe3qjdmj7/food.mp3?dl=1';

ght.playEndpoint('192.168.1.13', food);
