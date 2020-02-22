package edu.sjsu.crypto.ciphersys.classic;

import static org.junit.jupiter.api.Assertions.*;

import org.junit.jupiter.api.Test;

class A5_1SysTest {

	@Test
	void test() {
		/*
		String plaintext = "Defend the east wall!";
		String ciphertext = "1df58522801a1789fa5c9d48486efedbc3c39acc05";
		String pass = "GoToHell#007";
		
		A5_1Sys sys = new A5_1Sys (pass);
		System.out.println("ciphertext = [" + sys.encrypt(plaintext)+ "]");
		System.out.println("Recovered Plaintext = [" + sys.decrypt(ciphertext)+ "]");
		*/
		
		String plaintext = "My name is sida, I like this class!";
		String ciphertext = "4d79206e616d6520697320736964612c2049206c696b65207468697320636c61737321";
		String pass = "1";
		
		A5_1Sys sys = new A5_1Sys (pass);
		//System.out.println("ciphertext = [" + sys.encrypt(plaintext)+ "]");
		System.out.println("Recovered Plaintext = [" + sys.decrypt(ciphertext)+ "]");
	}

}
