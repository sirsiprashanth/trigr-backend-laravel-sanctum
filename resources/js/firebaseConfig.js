import { initializeApp } from "firebase/app";
import { getAnalytics, isSupported } from "firebase/analytics";
import { getFirestore } from "firebase/firestore";

const firebaseConfig = {
  apiKey: "AIzaSyCigZCPNGgr5-gF5a-P4uP-NYWb0usyVx4",
  authDomain: "trigr-community.firebaseapp.com",
  projectId: "trigr-community",
  storageBucket: "trigr-community.appspot.com",
  messagingSenderId: "1000776789412",
  appId: "1:1000776789412:web:f91dfd8aad45ca1b9d6531",
  measurementId: "G-1NM1K1CMJ5",
};

// Initialize Firebase
const app = initializeApp(firebaseConfig);

// Check if analytics is supported
let analytics;
isSupported().then((supported) => {
  if (supported) {
    analytics = getAnalytics(app);
  } else {
    console.warn("Firebase Analytics is not supported in this environment.");
  }
});

const db = getFirestore(app);

export { db };
